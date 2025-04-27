<?php

namespace App\Http\Controllers;

use App\Models\InventoryOrderItem;
use App\Models\StockAdjustmentLog;
use App\Models\ProductUnitRate;
use App\Models\StoreCategory;
use App\Models\InventoryOrder;
use App\Models\StoreEmployee;
use Illuminate\Http\Request;
use App\Models\DeviceToken;
use App\Models\Inventory;
use App\Models\Category;
use App\Models\OrderLog;
use App\Helpers\Helper;
use App\Models\Product;
use App\Models\Metric;
use App\Models\Stock;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;

class IncomingOrderController extends Controller
{
    public function index(Request $request) {
        if ($request->ajax()) {
            $orders = InventoryOrder::with('items')
            ->whereHas('store', function ($builder) {
                $builder->where('is_franchise', 0);
            });

            if (!in_array(auth()->user()->roles[0]->id, [Helper::$roles['inventory-manager'], Helper::$roles['admin']])) {
                if (auth()->user()->roles[0]->id == Helper::$roles['store-manager']) {
                    $storeId = StoreEmployee::where('employee_id', auth()->user()->id)->where('is_store_manager', 1)->first()->store_id ?? null;
                    $storeId = Store::where('id', $storeId)->first()->id ?? null;
                    if (!is_null($storeId)) {
                        $orders = $orders->where('store_id', $storeId);
                    } else {
                        $orders = $orders->where('id', 0);
                    }
                } else {
                    $orders = $orders->where('id', 0);
                }
            }

            if (!empty($request->store)) {
                $storeFilter = is_string($request->store) ? explode(',', $request->store) : (is_array($request->store) ? $request->store : []);
                $storeFilter = array_filter($storeFilter, function ($el) {
                    if (is_numeric($el) && $el > 0) {
                        return $el;
                    }
                });
                $orders = $orders->whereIn('store_id', $storeFilter);
            }

            
            if (!empty($request->status)) {
                $statusFilter = array_map(function ($el) {
                    if (is_numeric($el) && $el >= 0) {
                        return $el;
                    }
                }, explode(',', $request->status));

                $orders = $orders->whereIn('status', $statusFilter);
            }

            $fromDate = Carbon::parse($request->fromDate)->format('Y-m-d H:i');
            $toDate = Carbon::parse($request->toDate)->format('Y-m-d H:i');

            if (!empty($request->fromDate) && empty($request->toDate)) {
                $orders = $orders->where(\DB::raw("DATE_FORMAT(date, '%Y-%m-%d %H:%i')"), '>=', $fromDate);
            } else if (empty($request->fromDate) && !empty($request->toDate)) {
                $orders = $orders->where(\DB::raw("DATE_FORMAT(date, '%Y-%m-%d %H:%i')"), '<=', $toDate);
            } else if (!empty($request->fromDate) && !empty($request->toDate)) {
                $orders = $orders->where(\DB::raw("DATE_FORMAT(date, '%Y-%m-%d %H:%i')"), '>=', $fromDate)->where(\DB::raw("DATE_FORMAT(date, '%Y-%m-%d %H:%i')"), '<=', $toDate);
            }

            if ($request->test == '1') {
                $orders = $orders->whereHas('store', function ($builder) {
                    return $builder->where('is_test_record', 1);
                });
            } else {
                $orders = $orders->whereHas('store', function ($builder) {
                    return $builder->where('is_test_record', 0);
                });
            }

            return datatables()
            ->eloquent($orders->orderBy('id', 'DESC'))
            ->editColumn('store_id', function ($row) {
                return $row->store->name ?? '-';
            })
            ->editColumn('date', function ($row) {
                return date('d-m-Y H:i', strtotime($row->date));
            })
            ->addColumn('amt', function ($row) {
                return  "<strong> $" . number_format((($row->items->sum('unitrate_amount') ?? 0) + $row->miscellenous_cost + $row->tax_amount + $row->shipping_cost), 2) . "</strong>";
            })
            ->editColumn('status', function ($row) {
                if ($row->status == 0) {
                    return '<span class="badge bg-warning">PENDING</span>';
                } else if ($row->status == 1) {
                    return '<span class="badge bg-primary">APPROVED</span>';
                } else if ($row->status == 2) {
                    return '<span class="badge bg-danger">CANCELLED</span>';
                } else if ($row->status == 3) {
                    return '<span class="badge bg-danger">REJECTED</span>';
                } else if ($row->status == 4) {
                    return '<span class="badge bg-primary">DISPATCHED</span>';
                } else if ($row->status == 5) {
                    return '<span class="badge bg-success">DELIVERED</span>';
                } else {
                    return '-';
                }
            })
            ->addColumn('action', function ($row) {
                $action = '';

                if (auth()->user()->can('incoming-orders.actions')) {
                    if (auth()->user()->roles[0]->id == Helper::$roles['inventory-manager'] || auth()->user()->roles[0]->id == Helper::$roles['admin'] || auth()->user()->roles[0]->id == Helper::$roles['store-manager']) {
                        if (in_array($row->status, [0, 1, 4, 5])) {
                            $action .= '<a href="'.route("incoming-orders.actions", encrypt($row->id)).'" class="btn btn-warning btn-sm me-2"> Edit </a>';
                        }
                    }
                }

                if (auth()->user()->can('incoming-orders.details')) {
                    $action .= '<a href="'.route("incoming-orders.details", encrypt($row->id)).'" class="btn btn-info btn-sm me-2"> Show </a>';
                }

                $action .= '<a href="'.route("export-single-order", encrypt($row->id)).'" class="btn btn-success btn-sm me-2"> Print </a>';

                return $action;
            })
            ->addIndexColumn()
            ->rawColumns(['status', 'amt', 'action'])
            ->toJson();
        }

        $page_title = 'Internal Orders';
        $stores = Store::where('status', 1)->withoutFranchise()->get();

        return view('orders.index', compact('page_title', 'stores'));
    }

    public function show(Request $request, $id) {
        $id = decrypt($id);
        $page_title = 'Order details';

        $order = InventoryOrder::where('id', $id)->with(['store' => function ($builder) {
            return $builder->withTrashed();
        },'deliverdby' => function ($builder) {
            return $builder->withTrashed();
        }, 'items.theproduct' => function ($builder) {
            return $builder->withTrashed();
        }])->first();
        $units = Metric::select('id', 'code')->withTrashed()->pluck('code', 'id')->toArray();
        $logs = OrderLog::where('order_id', $id)->orderBy('id', 'DESC')->get();
        $allUnits = ProductUnitRate::withTrashed()->pluck('name', 'id')->toArray();

        return view('orders.view', compact('page_title', 'order', 'units', 'logs', 'allUnits'));
    }

    function update(Request $request, $id) {
        if ($request->method() == 'PUT') {
            $rules = [
                'signature' => 'file|mimes:jpg,png,webp,jpeg'
            ];

            $validator = \Validator::make($request->all(), $rules);
        
            if ($validator->fails()) { 
                return redirect()->route('incoming-orders.index')->with('error', implode(",", $validator->messages()->all()));
            }

            \DB::beginTransaction();

            try {

                /** Order Updation Code **/

                $products = array_filter($request->product);

                if (empty($products)) {
                    \DB::rollBack();
                    return redirect()->route('incoming-orders.index')->with('error', 'To move forward, please add items to your order');
                }

                $order = InventoryOrder::find(decrypt($id));

                if (!($order instanceof InventoryOrder)) {
                    \DB::rollBack();
                    return redirect()->route('incoming-orders.index')->with('error', 'Not able to find the order you were looking for');
                }

                if ($order->status == 2 || $order->status == 3) {
                    \DB::rollBack();
                    return redirect()->route('incoming-orders.index')->with('error', 'The store has rejected or you cancelled this order, so you can\'t make any changes to it.');
                }

                if ($order->status == 5) {
                    \DB::rollBack();
                    return redirect()->route('incoming-orders.index')->with('error', 'Since the order has already been delivered, you can\'t make any changes.');
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
                            'log' => 'Order expected delivery date changed from ' . date('d-m-Y', strtotime($order->delivery_date)) . ' to ' . date('d-m-Y', strtotime($request->delivery_date)),
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
                        'type' => 1,
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

                $editableItems = is_array($request->editid) && !empty($request->editid) ? array_values($request->editid) : [];

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

                            if (isset($request->editid[$key])) {

                                $thisExistingItem = InventoryOrderItem::firstWhere('id', $request->editid[$key]);

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

                                InventoryOrderItem::where('id', $request->editid[$key])->update([
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
                                if (Inventory::where('type', 1)->where('table_id', Helper::$inventoryTables[1])->where('record_id', $request->editid[$key])->exists()) {
                                    Inventory::where('type', 1)->where('table_id', Helper::$inventoryTables[1])->where('record_id', $request->editid[$key])->update([
                                        'product_id' => $product,
                                        'unit_id' => $subProductRow,
                                        'quantity' => (floatval($request->qty[$key]) ?? 0) * ($thisSubProduct && $thisSubProduct->is_tray_order_item == 1 && $thisSubProduct->packet > 0 ? $thisSubProduct->packet : 1),
                                    ]);
                                } else {
                                    Inventory::create([
                                        'table_id' => Helper::$inventoryTables[1],
                                        'record_id' => $request->editid[$key],
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
                                    'record_id' => $request->editid[$key],
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
                            return redirect()->route('incoming-orders.index')->with('error', 'Order item is invalid.');
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
                                        'order_editid' => $thisItem->id,
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
                                        'order_editid' => $thisItem->id,
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

                return redirect()->route('incoming-orders.index')->with('success', 'Order Updated successfully.');
            } catch (\Exception $e) {
                $exception = 'ORDER UPDATE API ERROR: ' . $e->getMessage() . ' ON LINE: ' . $e->getLine();

                \Log::error($exception);
                \DB::rollBack();

                return redirect()->route('incoming-orders.index')->with('error', 'Something went wrong.');
            }
        } else {

            $page_title = 'Edit Order';
            $decryptedId = decrypt($id);

            $order = InventoryOrder::where('id', $decryptedId)->with(['store' => function ($builder) {
                return $builder->withTrashed();
            }, 'deliverdby' => function ($builder) {
                return $builder->withTrashed();
            }, 'deliverdby.workingstore.store' => function ($builder) {
                return $builder->withTrashed();
            }, 'items.theproduct' => function ($builder) {
                return $builder->withTrashed();
            }])->first();

            $logs = OrderLog::where('order_id', $decryptedId)->orderBy('id', 'DESC')->get();
    
            return view('orders.edit', compact('page_title', 'order', 'logs', 'id'));
        }
    }

    public function create(Request $request) {
        if ($request->method() == 'POST') {

            \DB::beginTransaction();

            try {

                $iO = new InventoryOrder();
                $iO->store_id = $request->store;
                $iO->manager_id = in_array(Helper::$roles['store-manager'], auth()->user()->roles()->pluck('id')->toArray()) ? auth()->user()->id : (StoreEmployee::select('employee_id')->where('store_id', $request->store)->where('is_store_manager', 1)->first()->employee_id);
                $iO->order_no = Helper::generateOrderNumber();
                $iO->delivery_person = $request->delivery_person;
                $iO->delivery_date = date('Y-m-d H:i:s', strtotime($request->delivery_date));
                $iO->description = $request->description;
                $iO->date = now();
                $iO->save();

                $id = $iO->id;

                $poItems = [];

                foreach ($request->product as $key => $product) {
                    $poItems[] = [
                        'order_id' => $id,
                        'product_id' => $product,
                        'unitrate_id' => $request->unit[$key] ?? null,
                        'unitrate_price' => floatval($request->price[$key]) ?? 0,
                        'unitrate_qty' => floatval($request->qty[$key]) ?? 0,
                        'unitrate_amount' => (floatval($request->amount[$key])) ?? 0,
                        'created_at' => now(),
                        'is_unit' => 1
                    ];
                }

                InventoryOrderItem::insert($poItems);

                Helper::storeLog(0, 1,[
                    'type' => 3,
                    'order_number' => $iO->order_no,
                    'column' => 'id',
                    'order_id' => $iO->id,
                    'store_id' => $iO->store_id,
                    'user_id' => $iO->manager_id,
                    'comment' => 'Order created'
                ]);

                $oldDataOfOrderForLog = InventoryOrder::find($id);
                $thisSto = Store::find($request->store);
    
                if ($oldDataOfOrderForLog && $thisSto) {
                    $subTotalOfItems = $oldDataOfOrderForLog->items()->sum('unitrate_amount');
                    $taxAmount = 0;
    
                    if ($thisSto->tax_applicable && $thisSto->tax_percentage > 0 && $subTotalOfItems > 0) {
                        $taxAmount = ($subTotalOfItems * $thisSto->tax_percentage) / 100;
                    }
    
                    $newOrderRecord = InventoryOrder::find($id);
                    $newOrderRecord->miscellenous_cost = $thisSto->miscellenous_cost;
                    $newOrderRecord->shipping_cost = $thisSto->shipping_cost;
                    $newOrderRecord->tax_applicable = $thisSto->tax_applicable;
                    $newOrderRecord->tax_percentage = $thisSto->tax_percentage;
                    $newOrderRecord->tax_amount = $taxAmount;
                    $newOrderRecord->save();
                }

                \DB::commit();

                // Send Notification to Store Manager, Inventory Manager & Admin //
                $allInventoryManagers = User::whereHas('roles', function ($builder) {
                    return $builder->whereIn('id', [Helper::$roles['inventory-manager'], Helper::$roles['admin']]);
                })->select('id')->pluck('id')->toArray();

                if (!empty($allInventoryManagers)) {
                    foreach ($allInventoryManagers as $thisIMid) {
                        $userEmail = User::find($thisIMid);
                        if (isset($userEmail->email) && filter_var($userEmail->email, FILTER_VALIDATE_EMAIL)) {
                            \App\Jobs\SendMailWithPushNotification::dispatch('ORDER_CREATION', [
                                'invOrder' => $iO,
                                'userEmail' => $userEmail
                            ]);
                        }
                    }
                }

                $allAdminsAndManager = [$iO->manager_id];

                if (!empty($allInventoryManagers)) {
                    $allAdminsAndManager = array_merge($allAdminsAndManager, $allInventoryManagers);
                }

                $deviceTokens = DeviceToken::select('token')->whereIn('user_id', $allAdminsAndManager)->pluck('token')->toArray();
                if (!empty($deviceTokens)) {
                    $title = strip_tags($oldDataOfOrderForLog->order_no);

                    \App\Jobs\SendMailWithPushNotification::dispatch('PUSH_NOTIFICATION_3', [
                        'deviceTokens' => $deviceTokens,
                        'oldDataOfOrderForLog' => $oldDataOfOrderForLog,
                        'useridfortoken' => $iO->manager_id,
                        'notificationTitle' => $title
                    ]);
                }
                // Send Notification to Store Manager, Inventory Manager & Admin //

                return redirect()->route('incoming-orders.index')->with('success', 'Order added successfully');
            } catch (\Exception $e) {
                \Log::error($e->getMessage() . ' ' . $e->getLine());
                \DB::rollBack();
                return redirect()->route('incoming-orders.index')->with('error', 'Something went wrong');
            }

        } else {
            $page_title = 'Add Order';
            $orderNo = Helper::generateOrderNumber();
            $stores = Store::withmanager()->withoutFranchise()->get();
            return view('orders.create', compact('page_title', 'orderNo', 'stores'));
        }
    }

    public function exportOrderPdf(Request $request) {
        $orders = InventoryOrder::with([
            'store' => function ($builder) {
            return $builder->withTrashed();
        }, 'managerobj' => function ($builder) {
            return $builder->withTrashed();
        }, 'items', 'items.theproduct' => function ($builder) {
            return $builder->withTrashed();
        }, 'items.unitrate' => function ($builder) {
            return $builder->withTrashed();
        }
        ]);

        if (auth()->check() && !in_array(auth()->user()->roles[0]->id, [Helper::$roles['inventory-manager'], Helper::$roles['admin']])) {
            if (auth()->user()->roles[0]->id == Helper::$roles['store-manager']) {
                $storeId = StoreEmployee::where('employee_id', auth()->user()->id)->where('is_store_manager', 1)->first()->store_id ?? null;
                $storeId = Store::where('id', $storeId)->first()->id ?? null;
                if (!is_null($storeId)) {
                    $orders = $orders->where('store_id', $storeId);
                } else {
                    $orders = $orders->where('id', 0);
                }
            } else {
                $orders = $orders->where('id', 0);
            }
        }

        if (!empty($request->store)) {
            $storeFilter = is_string($request->store) ? explode(',', $request->store) : (is_array($request->store) ? $request->store : []);
            $storeFilter = array_filter($storeFilter, function ($el) {
                if (is_numeric($el) && $el > 0) {
                    return $el;
                }
            });

            $orders = $orders->whereIn('store_id', $storeFilter);
        }

        if (!empty($request->status)) {
            if (is_array($request->status)) {
                $statusFilter = array_map(function ($el) {
                    if (is_numeric($el) && $el >= 0) {
                        return $el;
                    }
                }, $request->status);
            } else {
                $statusFilter = explode(',', $request->status);
            }

            $orders = $orders->where(function ($builder) use ($statusFilter) {
                $builder->orWhereIn('status', $statusFilter);
            });
        }

        $fromDate = Carbon::parse($request->fromDate)->format('Y-m-d H:i');
        $toDate = Carbon::parse($request->toDate)->format('Y-m-d H:i');

        if (!empty($request->fromDate) && empty($request->toDate)) {
            $orders = $orders->where(\DB::raw("DATE_FORMAT(date, '%Y-%m-%d %H:%i')"), '>=', $fromDate);
        } else if (empty($request->fromDate) && !empty($request->toDate)) {
            $orders = $orders->where(\DB::raw("DATE_FORMAT(date, '%Y-%m-%d %H:%i')"), '<=', $toDate);
        } else if (!empty($request->fromDate) && !empty($request->toDate)) {
            $orders = $orders->where(\DB::raw("DATE_FORMAT(date, '%Y-%m-%d %H:%i')"), '>=', $fromDate)->where(\DB::raw("DATE_FORMAT(date, '%Y-%m-%d %H:%i')"), '<=', $toDate);
        }

        if ($request->test == '1') {
            $orders = $orders->whereHas('store', function ($builder) {
                return $builder->where('is_test_record', 1);
            });
        } else {
            $orders = $orders->whereHas('store', function ($builder) {
                return $builder->where('is_test_record', 0);
            });
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('order-report', ['data' => $orders->orderBy('id', 'DESC')->get()])->setPaper('A4', 'landscape');
            
        return $pdf->download('order-report.pdf');
    }

    public function exportOrderExcel(Request $request) {
        $orders = InventoryOrder::with([
            'store' => function ($builder) {
            return $builder->withTrashed();
        }, 'managerobj' => function ($builder) {
            return $builder->withTrashed();
        }, 'items', 'items.theproduct' => function ($builder) {
            return $builder->withTrashed();
        },  'items.unitrate' => function ($builder) {
            return $builder->withTrashed();
        }
        ]);

        if (auth()->check() && !in_array(auth()->user()->roles[0]->id, [Helper::$roles['inventory-manager'], Helper::$roles['admin']])) {
            if (auth()->user()->roles[0]->id == Helper::$roles['store-manager']) {
                $storeId = StoreEmployee::where('employee_id', auth()->user()->id)->where('is_store_manager', 1)->first()->store_id ?? null;
                $storeId = Store::where('id', $storeId)->first()->id ?? null;
                if (!is_null($storeId)) {
                    $orders = $orders->where('store_id', $storeId);
                } else {
                    $orders = $orders->where('id', 0);
                }
            } else {
                $orders = $orders->where('id', 0);
            }
        }

        if (!empty($request->store)) {
            $storeFilter = is_string($request->store) ? explode(',', $request->store) : (is_array($request->store) ? $request->store : []);
            $storeFilter = array_filter($storeFilter, function ($el) {
                if (is_numeric($el) && $el > 0) {
                    return $el;
                }
            });

            $orders = $orders->whereIn('store_id', $storeFilter);
        }

        if (!empty($request->status)) {
            if (is_array($request->status)) {
                $statusFilter = array_map(function ($el) {
                    if (is_numeric($el) && $el >= 0) {
                        return $el;
                    }
                }, $request->status);
            } else {
                $statusFilter = explode(',', $request->status);
            }

            $orders = $orders->where(function ($builder) use ($statusFilter) {
                $builder->orWhereIn('status', $statusFilter);
            });
        }

        $fromDate = Carbon::parse($request->fromDate)->format('Y-m-d H:i');
        $toDate = Carbon::parse($request->toDate)->format('Y-m-d H:i');

        if (!empty($request->fromDate) && empty($request->toDate)) {
            $orders = $orders->where(\DB::raw("DATE_FORMAT(date, '%Y-%m-%d %H:%i')"), '>=', $fromDate);
        } else if (empty($request->fromDate) && !empty($request->toDate)) {
            $orders = $orders->where(\DB::raw("DATE_FORMAT(date, '%Y-%m-%d %H:%i')"), '<=', $toDate);
        } else if (!empty($request->fromDate) && !empty($request->toDate)) {
            $orders = $orders->where(\DB::raw("DATE_FORMAT(date, '%Y-%m-%d %H:%i')"), '>=', $fromDate)->where(\DB::raw("DATE_FORMAT(date, '%Y-%m-%d %H:%i')"), '<=', $toDate);
        }

        if ($request->test == '1') {
            $orders = $orders->whereHas('store', function ($builder) {
                return $builder->where('is_test_record', 1);
            });
        } else {
            $orders = $orders->whereHas('store', function ($builder) {
                return $builder->where('is_test_record', 0);
            });
        }

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\OrderExport($orders->orderBy('id', 'DESC')->get()),'orders.xlsx');
    }

    public function getUserData(Request $request)
    {
        $queryString = trim($request->searchQuery);
        $excludeId = $request->id;
        $page = $request->input('page', 1);
        $limit = 10;
    
        $query = User::with('workingstore.store')->when(is_numeric($excludeId) && $excludeId != '0', function ($builder) use ($excludeId) { 
            return $builder->where('id', '!=', $excludeId);
        })->whereHas('roles', function ($builder) {
            return $builder->where('id', Helper::$roles['employee']);
        });
    
        if (!empty($queryString)) {
            $query->where('name', 'LIKE', "%{$queryString}%")
            ->orWhere('username', 'LIKE', "%{$queryString}%");
        }
    
        $data = $query->paginate($limit, ['*'], 'page', $page);
    
        return response()->json([
            'items' => $data->map(function ($pro) {
                return [
                    'id' => $pro->id,
                    'text' => $pro->name . (isset($pro->workingstore->store->name) ? " - {$pro->workingstore->store->name}" : '')
                ];
            }),
            'pagination' => [
                'more' => $data->hasMorePages()
            ]
        ]);
    }

    public function exportSingleOrder(Request $request, $id) {
        try {

            $id = decrypt($id);
            $order = InventoryOrder::with([
                'store' => function ($builder) {
                return $builder->withTrashed();
            }, 'managerobj' => function ($builder) {
                return $builder->withTrashed();
            }, 'items', 'items.theproduct' => function ($builder) {
                return $builder->withTrashed();
            }, 'items.unitrate' => function ($builder) {
                return $builder->withTrashed();
            }
            ])
            ->where('id', $id)
            ->first();

            $page_title = 'Order #' . $order->order_no;
            $orderStatus = Helper::$orderStatuses[$order->status];
            $allUnits = ProductUnitRate::withTrashed()->pluck('name', 'id')->toArray();

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('orders.print', compact('order','order','page_title','orderStatus', 'allUnits'));
            return $pdf->stream('invoice.pdf');

        } catch (\Exception $e) {
            dd($e->getMessage() . $e->getLine());
            abort(404);
        }
    }

    public function manageB2BCategories(Request $request, $id) {
        if ($request->method() == 'GET' && $request->ajax()) {

            $categories = StoreCategory::with(['category'])
            ->whereHas('category', function ($builder) {
                return $builder->where('id', '>', 0);
            })
            ->where('store_id', decrypt($id));

            return datatables()
            ->eloquent($categories)
            ->filter(function ($row) {
                if (isset(request('search')['value'])) {
                    $row->where(function ($innerRow) {
                        $innerRow->whereHas('category', function ($innerBuilder2) {
                            $innerBuilder2->where('name', 'LIKE', '%' . request('search')['value'] . '%');
                        });
                    });
                }
            })
            ->addColumn('name', function ($row) {
                return $row->category->name ?? '';
            })
            ->addColumn('action', function ($row) {
                return ' <button data-rowid="' . $row->id . '" class="btn-sm btn btn-danger delete-btn-category"> Delete </button>';
            })
            ->rawColumns(['action'])
            ->toJson();

        } else if ($request->method() == 'POST') {

            $store = Store::find(decrypt($id));
            $allCategoriesIds = explode(',', $request->ids);

            if (!empty($allCategoriesIds)) {
                foreach ($allCategoriesIds as $thisCategory) {
                    StoreCategory::updateOrCreate([
                        'store_id' => $store->id,
                        'category_id' => $thisCategory
                    ]);
                }

                return response()->json(['status' => true, 'message' => 'Categories added successfully.']);
            }

            return response()->json(['status' => false, 'message' => 'Select categories.']);

        } else if ($request->method() == 'GET') {
            
            $store = Store::find(decrypt($id));
    
            $page_title = 'Manage B2B Order\'s Categories';
            return view('stores.categories', compact('page_title', 'store', 'id'));
        } else if ($request->method() == "DELETE") {
            StoreCategory::where('id', $request->id)->delete();
            
            return response()->json(['status' => true, 'message' => 'Categories deleted successfully.']);
        }
    }

    public function storeB2BCategories(Request $request) {
        $queryString = trim($request->searchQuery);
        $page = $request->input('page', 1);
        $limit = env('SELECT2_PAGE_LENGTH', 5);
    
        $storeNotAvailableCategories = StoreCategory::where('store_id', $request->store)->pluck('category_id')->toArray();

        $query = Category::whereNotIn('id', $storeNotAvailableCategories);
    
        if (!empty($queryString)) {
            $query->where('name', 'LIKE', "%{$queryString}%");
        }
    
        $data = $query->paginate($limit, ['*'], 'page', $page);
    
        return response()->json([
            'items' => $data->map(function ($pro) {
                return [
                    'id' => $pro->id,
                    'text' => $pro->name
                ];
            }),
            'pagination' => [
                'more' => $data->hasMorePages()
            ]
        ]);
    }
}
