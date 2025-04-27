<?php

use App\Models\InventoryOrder;
    function updateOrder(Request $request) {

        $rules = [
            'store_id' => 'required|numeric|exists:stores,id',
            'user_id' => 'required|numeric|exists:users,id',
            'order_id' => 'required|numeric|exists:inventory_orders,id',
            'status' => 'required|numeric|in:0,1,2,3,4,5',
            'product_id' => 'required|exists:products,id',
            'product_id.*' => 'required|distinct',
            'unit' => 'required|exists:product_unit_rates,id',
            'unit.*' => 'required',
            'qty' => 'required',
            'qty.*' => 'required|numeric|min:1',
            'price' => 'required',
            'price.*' => 'required|numeric|min:0',
            'signature' => 'file|mimes:jpg,png,webp,jpeg'
        ];

        $validator = \Validator::make($request->all(), $rules);
    
        if ($validator->fails()) { 
            return response()->json(['error' => implode(",", $validator->messages()->all())], 401);
        }

        \DB::beginTransaction();

        try {

            /** Order Updation Code **/

            $products = array_filter($request->product_id);

            if (empty($products)) {
                \DB::rollBack();
                return response()->json(['success' => 'To move forward, please add items to your order.']);
            }

            $order = InventoryOrder::find($request->order_id);

            if (!($order instanceof InventoryOrder)) {
                \DB::rollBack();
                return response()->json(['success' => 'Not able to find the order you were looking for']);
            }

            if ($order->status == 2 || $order->status == 3) {
                \DB::rollBack();
                return response()->json(['success' => 'The store has rejected or you cancelled this order, so you can\'t make any changes to it.']);
            }

            if ($order->status == 5) {
                \DB::rollBack();
                return response()->json(['success' => 'Since the order has already been delivered, you can\'t make any changes.']);
            }

            $recordLogFor = $dataToBeUpdated = [];

            if ($request->has('status')) {
                if ($request->status != $order->status) {
                    $dataToBeUpdated['status'] = $request->status;
                    $recordLogFor[] = [
                        'old' => $order->status,
                        'new' => $request->status,
                        'type' => 2,
                        'order_number' => $order->order_no,
                        'column' => 'status',
                        'order_id' => $order->id,
                        'store_id' => $order->store_id,
                        'user_id' => $order->manager_id,
                        'newcomment' => $request->comment,
                        'log_type' => 0
                    ];
                }
            }

            if ($request->has('date')) {
                if (date('Y-m-d', strtotime($request->date)) != date('Y-m-d', strtotime($order->date))) {
                    $dataToBeUpdated['date'] = date('Y-m-d', strtotime($request->date));
                    $recordLogFor[] = [
                        'old' => date('Y-m-d', strtotime($order->date)),
                        'new' => date('Y-m-d', strtotime($request->date)),                        
                        'type' => 3,
                        'order_number' => $order->order_no,
                        'column' => 'date',
                        'order_id' => $order->id,
                        'store_id' => $order->store_id,
                        'user_id' => $order->manager_id,
                        'comment' => 'Order date changed from ' . date('d-m-Y', strtotime($order->date)) . ' to ' . date('d-m-Y', strtotime($request->date)),
                        'log_type' => 0
                    ];
                }
            }

            if ($request->has('delivery_date')) {
                if (date('Y-m-d', strtotime($request->delivery_date)) != date('Y-m-d', strtotime($order->delivery_date))) {
                    $dataToBeUpdated['delivery_date'] = date('Y-m-d', strtotime($request->delivery_date));
                    $recordLogFor[] = [
                        'old' => date('Y-m-d', strtotime($order->delivery_date)),
                        'new' => date('Y-m-d', strtotime($request->delivery_date)),
                        'column' => 'delivery_date',
                        'order_number' => $order->order_no,
                        'order_id' => $order->id,
                        'store_id' => $order->store_id,
                        'user_id' => $order->manager_id,
                        'type' => 3,
                        'comment' => 'Order expected delivery date changed from ' . date('d-m-Y', strtotime($order->delivery_date)) . ' to ' . date('d-m-Y', strtotime($request->delivery_date)),
                        'log_type' => 0
                    ];
                }
            }

            if ($request->has('delivery_person')) {
                if ($request->delivery_person != $order->delivery_person) {
                    $dataToBeUpdated['delivery_person'] = $request->delivery_person;

                    $oldDeliveryDriverName = User::select('name')->withTrashed()->where('id', $order->delivery_person)->first();
                    $newDeliveryDriverName = User::select('name')->withTrashed()->where('id', $request->delivery_person)->first();

                    if ($oldDeliveryDriverName) {
                        $comment = "Delivery person has been changed from {$oldDeliveryDriverName->name} to {$newDeliveryDriverName->name}";
                    } else {
                        $comment = "Delivery has been assigned to {$newDeliveryDriverName->name}";
                    }

                    $recordLogFor[] = [
                        'new' => $request->delivery_person,
                        'comment' => 'Order expected delivery date changed from ' . date('d-m-Y', strtotime($order->delivery_date)) . ' to ' . date('d-m-Y', strtotime($request->delivery_date)),
                        'type' => 3,
                        'column' => 'delivery_person',
                        'old' => $order->delivery_person,
                        'log_type' => 0,

                        'order_number' => $order->order_no,
                        'order_id' => $order->id,
                        'store_id' => $order->store_id,
                        'user_id' => $order->manager_id,
                        'delivery_person' => $request->delivery_person,
                        'old_delivery_person' => $order->delivery_person,
                        'comment' => $comment
                    ];
                }
            }

            if ($request->has('comment') && !empty(trim($request->comment))) {
                $dataToBeUpdated['comment'] = $request->comment;
                $recordLogFor[] = [
                    'old' => 0,
                    'new' => 1,
                    'column' => 'comment',
                    'order_number' => $order->order_no,
                    'comment' => $request->comment,
                    'order_id' => $order->id,
                    'store_id' => $order->store_id,
                    'user_id' => $order->manager_id
                ];
            }

            if (($order->status == 4 && $request->hasFile('signature')) || ($request->status == 5 && $request->hasFile('signature')) || ($request->status == 5)) {
                if ($request->hasFile('signature')) {
                    if (!file_exists(storage_path('app/public/orders/signatures'))) {
                        mkdir(storage_path('app/public/orders/signatures'), 0777, true);
                    }
    
                    $sign = 'SIGN-' . date('YmdHis') . uniqid() . '.' . $request->file('signature')->getClientOriginalExtension();
                    $request->file('signature')->move(storage_path('app/public/orders/signatures'), $sign);
    
                    $dataToBeUpdated['signature'] = $sign;
                    $dataToBeUpdated['is_signature'] = 1;

                    $recordLogFor[] = [
                        'old' => $order->signature,
                        'new' => $sign,
                        'column' => 'signature',
                        'type' => 3,
                        'order_number' => $order->order_no,
                        'order_id' => $order->id,
                        'store_id' => $order->store_id,
                        'user_id' => $order->manager_id,
                        'comment' => 'New signature uploaded'
                    ];
                }

                $dataToBeUpdated['delivered_at'] = now();
                $dataToBeUpdated['status'] = 5;
            }

            $currentProductsInOrder = $order->items()->pluck('product_id')->toArray();

            $productEloquent = Product::select('id', 'name')
            ->when(!empty($currentProductsInOrder), function ($builder) use ($currentProductsInOrder) {
                $builder->whereIn('id', $currentProductsInOrder);
            })
            ->withTrashed()
            ->pluck('name', 'id')
            ->toArray();

            $subProductEloquent = ProductUnitRate::select('id', 'name')
            ->when(!empty($currentProductsInOrder), function ($builder) use ($currentProductsInOrder) {
                $builder->whereHas('product', function ($innerBuilder) use ($currentProductsInOrder) {
                    $innerBuilder->whereIn('id', $currentProductsInOrder);
                });
            })
            ->withTrashed()
            ->pluck('name', 'id')
            ->toArray();

            $editableItems = is_array($request->item_id) && !empty($request->item_id) ? array_values($request->item_id) : [];

            $StockToBeRemoved = InventoryOrderItem::where('order_id', $order->id)
            ->whereNotIn('id', $editableItems)
            ->pluck('id')
            ->toArray();

            Inventory::where('table_id', Helper::$inventoryTables[1])
            ->whereIn('record_id', $StockToBeRemoved)
            ->delete();

            $itemsToBeDeleted = InventoryOrderItem::whereIn('id', $StockToBeRemoved)->get()->toArray();
            if (!empty($itemsToBeDeleted)) {
                foreach ($itemsToBeDeleted as $thisDeletableItemId) {
                    if (isset($productEloquent[$thisDeletableItemId['product_id']]) && isset($subProductEloquent[$thisDeletableItemId['unitrate_id']])) {
                        $recordLogFor[] = [
                            'old' => 0,
                            'new' => 1,
                            'column' => 'item',
                            'comment' => "Product <strong> " . $productEloquent[$thisDeletableItemId['product_id']] . " - " . $subProductEloquent[$thisDeletableItemId['unitrate_id']] . " </strong> has been removed from this order.</strong>",
                            'type' => 3,
                            'order_number' => $order->order_no,
                            'order_id' => $order->id,
                            'store_id' => $order->store_id,
                            'user_id' => $order->manager_id,
                            'log_type' => 1
                        ];
                    }

                    InventoryOrderItem::where('id', $thisDeletableItemId['id'])->delete();
                }
            }
            
            foreach ($products as $key => $product) {

                $productRow = $product ?? null;
                $subProductRow = isset($request->unit[$key]) ? $request->unit[$key] : null;

                if (is_numeric($productRow) && is_numeric($subProductRow)) {
                    $thisSubProduct = ProductUnitRate::withTrashed()->where('id', $subProductRow)->where('product_id', $productRow)->first();
                    $thisProduct = Product::withTrashed()->firstWhere('id', $productRow);

                    if ($thisProduct && $thisSubProduct) {
                        $productName = $thisProduct->name;
                        $subProductName = $thisSubProduct->name;

                        if (isset($request->item_id[$key])) {

                            $thisExistingItem = InventoryOrderItem::firstWhere('id', $request->item_id[$key]);

                            $oldPrice = $thisExistingItem->unitrate_price ?? 0;
                            $oldQuantity = $thisExistingItem->unitrate_qty ?? 0;
                            $oldProductId = $thisExistingItem->product_id ?? 0;
                            $oldSubProductId = $thisExistingItem->unitrate_id ?? 0;

                            if (isset($productEloquent[$oldProductId]) && $oldProductId > 0) {
                                $oldItemProductName = $productEloquent[$oldProductId];
                            } else {
                                $oldItemProductName = '';
                            }
            
                            if (isset($subProductEloquent[$oldSubProductId]) && $oldSubProductId > 0) {
                                $oldItemSubProductName = $subProductEloquent[$oldSubProductId];
                            } else {
                                $oldItemSubProductName = '';
                            }

                            InventoryOrderItem::where('id', $request->item_id[$key])->update([
                                'product_id' => $product,
                                'unitrate_id' => $subProductRow,
                                'unitrate_price' => floatval($request->price[$key] ?? 0),
                                'unitrate_qty' => floatval($request->qty[$key] ?? 0),
                                'unitrate_amount' => (floatval($request->price[$key] ?? 0)) * (floatval($request->qty[$key] ?? 0)),
                                'is_unit' => 1
                            ]);

                            $recordLogFor[] = [
                                'column' => 'item',
                                'old' => $oldProductId,
                                'new' => $product,
                                'comment' => "Product <strong> {$oldItemProductName} </strong> has been replaced with <strong> {$productName} </strong>",
                                'type' => 3,
                                'order_number' => $order->order_no,
                                'order_id' => $order->id,
                                'store_id' => $order->store_id,
                                'user_id' => $order->manager_id,
                                'log_type' => 1
                            ];

                            $recordLogFor[] = [
                                'column' => 'item',
                                'old' => $oldSubProductId,
                                'new' => $request->unit[$key] ?? null,
                                'comment' => "Product unit <strong> {$oldItemProductName} - {$oldItemSubProductName} </strong> has been replaced with <strong> {$productName} - {$subProductName} </strong>",
                                'type' => 3,
                                'order_number' => $order->order_no,
                                'order_id' => $order->id,
                                'store_id' => $order->store_id,
                                'user_id' => $order->manager_id,
                                'log_type' => 1

                            ];

                            $recordLogFor[] = [
                                'column' => 'item',
                                'old' => $oldQuantity,
                                'new' => $request->qty[$key] ?? 0,
                                'comment' => "Product <strong> {$productName} - {$subProductName} </strong> quantity has been changed from <strong> {$oldQuantity} </strong> to <strong> " . floatval($request->qty[$key] ?? 0) . " </strong>",
                                'type' => 3,
                                'order_number' => $order->order_no,
                                'order_id' => $order->id,
                                'store_id' => $order->store_id,
                                'user_id' => $order->manager_id,
                                'log_type' => 1
                                
                            ];

                            $recordLogFor[] = [
                                'column' => 'item',
                                'old' => $oldPrice,
                                'new' => $request->price[$key] ?? 0,
                                'comment' => "Product <strong> {$productName} - {$subProductName} </strong> price has been changed from <strong> $ {$oldPrice} </strong> to <strong> $ " . floatval($request->price[$key] ?? 0) . " </strong>",
                                'type' => 3,
                                'order_number' => $order->order_no,
                                'user_id' => $order->manager_id,
                                'order_id' => $order->id,
                                'store_id' => $order->store_id,
                                'log_type' => 1

                            ];

                        } else {
                            InventoryOrderItem::create([
                                'order_id' => $order->id,
                                'product_id' => $productRow,
                                'unitrate_id' => $subProductRow,
                                'unitrate_price' => floatval($request->price[$key] ?? 0),
                                'unitrate_qty' => floatval($request->qty[$key] ?? 0),
                                'unitrate_amount' => (floatval($request->price[$key] ?? 0)) * (floatval($request->qty[$key] ?? 0)),
                                'created_at' => now(),
                                'is_unit' => 1
                            ]);

                            $recordLogFor[] = [
                                'old' => 0,
                                'new' => 1,
                                'column' => 'item',
                                'type' => 3,
                                'order_number' => $order->order_no,
                                'order_id' => $order->id,
                                'store_id' => $order->store_id,
                                'user_id' => $order->manager_id,
                                'comment' => "Product <strong> {$productName} - {$subProductName} </strong> added to order with <strong>" . (floatval($request->qty[$key] ?? 0)) . '</strong> quantity with price of <strong> $ ' . (floatval($request->price[$key] ?? 0)) . '</strong>',
                                'log_type' => 1
                            ];
                        }

                        /** Update Stocks **/

                        $stockUpdateCondition = $request->for_franchise == 1 ? ($request->status == 5 && $request->payment_status == 3) : ($request->status == 5);

                        if ($order->status != $request->status && $stockUpdateCondition) {
                            if (Inventory::where('type', 1)->where('table_id', Helper::$inventoryTables[1])->where('record_id', $request->item_id[$key])->exists()) {
                                Inventory::where('type', 1)->where('table_id', Helper::$inventoryTables[1])->where('record_id', $request->item_id[$key])->update([
                                    'product_id' => $product,
                                    'unit_id' => $subProductRow,
                                    'quantity' => (floatval($request->qty[$key]) ?? 0) * ($thisSubProduct && $thisSubProduct->is_tray_order_item == 1 && $thisSubProduct->packet > 0 ? $thisSubProduct->packet : 1),
                                ]);
                            } else {
                                Inventory::create([
                                    'table_id' => Helper::$inventoryTables[1],
                                    'record_id' => $request->item_id[$key],
                                    'product_id' => $product,
                                    'unit_id' => $request->unit[$key] ?? null,
                                    'store_id' => $order->store_id ?? null,
                                    'quantity' => (floatval($request->qty[$key]) ?? 0) * ($thisSubProduct && $thisSubProduct->is_tray_order_item == 1 && $thisSubProduct->packet > 0 ? $thisSubProduct->packet : 1),
                                    'type' => 1,
                                    'added_by' => auth()->user()->id,
                                    'is_tray_order_item' => $thisSubProduct ? ($thisSubProduct->is_tray_order_item == 1 ? 1 : 0) : 0,
                                    'packet' =>  $thisSubProduct ? ($thisSubProduct->is_tray_order_item == 1 && $thisSubProduct->packet > 0 ? $thisSubProduct->packet : 1) : 1
                                ]);
                            }     
                        } else if ($order->status != $request->status && in_array($request->status, [Helper::$orderStatuses2['rejected'], Helper::$orderStatuses2['cancelled']])) {
                            Inventory::create([
                                'table_id' => Helper::$inventoryTables[1],
                                'record_id' => $request->item_id[$key],
                                'product_id' => $product,
                                'unit_id' => $request->unit[$key] ?? null,
                                'store_id' => $order->store_id ?? null,
                                'quantity' => (floatval($request->qty[$key]) ?? 0) * ($thisSubProduct && $thisSubProduct->is_tray_order_item == 1 && $thisSubProduct->packet > 0 ? $thisSubProduct->packet : 1),
                                'type' => 0,
                                'added_by' => auth()->user()->id,
                                'is_tray_order_item' => $thisSubProduct ? ($thisSubProduct->is_tray_order_item == 1 ? 1 : 0) : 0,
                                'packet' =>  $thisSubProduct ? ($thisSubProduct->is_tray_order_item == 1 && $thisSubProduct->packet > 0 ? $thisSubProduct->packet : 1) : 1
                            ]);
                        }



                        /** Update Stocks **/

                    } else {
                        \DB::rollBack();
                        return response()->json(['error' => 'Order item is invalid']);
                    }
                }
            }
            
            if (!empty($dataToBeUpdated)) {
                InventoryOrder::where('id', $order->id)->update($dataToBeUpdated);
            }

            if ($order->status != $request->status && ($request->for_franchise == 1 ? ($request->status == 5 && $request->payment_status == 3) : ($request->status == 5))) {
                $stockNotRecordedYet = Stock::where('store_id', $order->store_id)->where('order_id', $order->id)->doesntExist();
                if ($stockNotRecordedYet) {
                    $allItems = InventoryOrderItem::where('order_id', $order->id)->get();
                    foreach ($allItems as $thisItem) {
    
                        $currentOpeningStock = StockAdjustmentLog::where('unit_id', $thisItem->unitrate_id)->where('store_id', $order->store_id)->orderBy('id', 'DESC')->first()->closing_stock ?? 0;
                        $anyDeliveriesOfToday = StockAdjustmentLog::where('unit_id', $thisItem->unitrate_id)->where('store_id', $order->store_id)->where('date', date('Y-m-d'))->orderBy('id', 'DESC')->first()->total_new_delivery ?? 0;
    
                        $thisUnitRateItem = ProductUnitRate::withTrashed()->where('id', $thisItem->unitrate_id)->first();
    
                        if ($thisUnitRateItem) {
                            if ($thisUnitRateItem->is_tray_order_item) {
                                Stock::create([
                                    'is_delivery' => 1,
                                    'product_id' => $thisItem->product_id,
                                    'store_id' => $order->store_id,
                                    'unit_id' => $thisItem->unitrate_id,
                                    'order_id' => $thisItem->order_id,
                                    'order_item_id' => $thisItem->id,
                                    'type' => 0,
                                    'date' => now(),
                                    'packet' =>  $thisUnitRateItem->packet > 0 ? $thisUnitRateItem->packet : 1,
                                    'quantity' => $thisItem->unitrate_qty * ($thisUnitRateItem->packet > 0 ? $thisUnitRateItem->packet : 1),
                                    'added_by' => auth()->user()->id,
                                    'is_tray_order_item' => 1,
                                ]);
                                
                                StockAdjustmentLog::create([
                                    'is_delivery' => 1,
                                    'product_id' => $thisItem->product_id,
                                    'store_id' => $order->store_id,
                                    'unit_id' => $thisItem->unitrate_id,
                                    'opening_stock' => $currentOpeningStock > 0 ? $currentOpeningStock : 0,
                                    'new_delivery' => ($thisItem->unitrate_qty * $thisUnitRateItem->packet),
                                    'total_new_delivery' => ($thisItem->unitrate_qty * $thisUnitRateItem->packet) + $anyDeliveriesOfToday,
                                    'date' => date('Y-m-d'),
                                    'closing_stock' => $currentOpeningStock + ($thisItem->unitrate_qty * $thisUnitRateItem->packet),
                                    'sold_stock' => 0
                                ]);
                            } else {
                                Stock::create([
                                    'is_delivery' => 1,
                                    'product_id' => $thisItem->product_id,
                                    'store_id' => $order->store_id,
                                    'unit_id' => $thisItem->unitrate_id,
                                    'order_id' => $thisItem->order_id,
                                    'order_item_id' => $thisItem->id,
                                    'type' => 0,
                                    'date' => now(),
                                    'quantity' => $thisItem->unitrate_qty,
                                    'added_by' => auth()->user()->id
                                ]);
    
                                StockAdjustmentLog::create([
                                    'is_delivery' => 1,
                                    'product_id' => $thisItem->product_id,
                                    'store_id' => $order->store_id,
                                    'unit_id' => $thisItem->unitrate_id,
                                    'opening_stock' => $currentOpeningStock > 0 ? $currentOpeningStock : 0,
                                    'new_delivery' => $thisItem->unitrate_qty,
                                    'total_new_delivery' => $thisItem->unitrate_qty + $anyDeliveriesOfToday,
                                    'date' => date('Y-m-d'),
                                    'closing_stock' => $currentOpeningStock + $thisItem->unitrate_qty,
                                    'sold_stock' => 0
                                ]);
                            }
                        }
                    }
                }
            }

            $order = InventoryOrder::find($order->id);
            $thisSto = Store::find($order->store_id);

            if ($order && $thisSto) {
                $subTotalOfItems = $order->items()->sum('unitrate_amount');
                $taxAmount = 0;

                if ($thisSto->tax_applicable && $thisSto->tax_percentage > 0 && $subTotalOfItems > 0) {
                    $taxAmount = ($subTotalOfItems * $thisSto->tax_percentage) / 100;
                }

                $newOrderRecord = InventoryOrder::find($order->id);
                $newOrderRecord->miscellenous_cost = $thisSto->miscellenous_cost;
                $newOrderRecord->shipping_cost = $thisSto->shipping_cost;
                $newOrderRecord->tax_applicable = $thisSto->tax_applicable;
                $newOrderRecord->tax_percentage = $thisSto->tax_percentage;
                $newOrderRecord->tax_amount = $taxAmount;
                $newOrderRecord->save();
            }

            /** Order Updation Code **/

            \DB::commit();


            /** Capture Logs **/

            if (!empty($recordLogFor)) {
                foreach ($recordLogFor as $log) {
                    Helper::storeLog($log['old'], $log['new'], $log);
                }
            }

            /** Capture Logs **/            

            return response()->json(['success' => 'Order Updated successfully']);
        } catch (\Exception $e) {
            $exception = 'ORDER UPDATE API ERROR: ' . $e->getMessage() . ' ON LINE: ' . $e->getLine();

            \Log::error($exception);
            \DB::rollBack();

            return response()->json(['error' => 'Something went wrong', 'exception' => $exception]);
        }
    }
