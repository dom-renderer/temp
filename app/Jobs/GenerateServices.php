<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\InventoryItem;
use Illuminate\Bus\Queueable;
use App\Helpers\Frequency;
use App\Models\Removal;
use App\Helpers\Helper;
use App\Models\Rental;
use App\Models\Task;

class GenerateServices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $recordId;
    protected $type;
    protected $shouldCancelOtherRemovals;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($recordId, $type, $shouldCancelOtherRemovals = false)
    {
        $this->shouldCancelOtherRemovals = $shouldCancelOtherRemovals;
        $this->recordId = $recordId;
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $rental = Rental::find($this->recordId);

        if ($rental) {
            $removalDates = [];

            $allDates = [
                'monday' => 1,
                'tuesday' => 2,
                'wednesday' => 3,
                'thursday' => 4,
                'friday' => 5,
                'saturday' => 6,
                'sunday' => 0
            ];

            $frequency_type = $rental->frequency_type;
            $firstRemovalDate = $rental->removal_date;
            $firstDeliverDate = $rental->delivery_date;
            $select_days = explode(',', $rental->days);
            $fromDate = $rental->from_date;
            $toDate = $rental->to_date;
            $freqTime = date('H:i', strtotime($rental->frequency_time));

            $firstParameter = date('d-m-Y H:i', strtotime($firstRemovalDate));
            $SecondParameter = date('d-m-Y H:i', strtotime($toDate));

            if ($rental->frequency_contract != 1) {
                $removalDates = [$fromDate, $toDate];

                $removalDates = collect($removalDates)->values()->toArray();

                \DB::beginTransaction();

                try {
                    if (!empty($removalDates)) {

                        $allCurrentRemovals = Removal::where('rental_id', $rental->id)->whereIn('status', [0, 1])->pluck('skip_unit_id', 'id')->toArray();

                        // Remove Current Tasks
                        if ($this->shouldCancelOtherRemovals) {
                            foreach ($allCurrentRemovals as $id => $theSkipUnitId) {
                                if ($id) {
                                    Removal::find( $id)->delete();
                                    $eloquentTasks = Task::where('removal_id', $id)->first();
                                    if ($eloquentTasks) {
                                        Task::find($eloquentTasks->id)->delete();

                                        InventoryItem::where('id', $theSkipUnitId)->update([
                                            'available' => 1,
                                            'task_id' => null,
                                            'customer_id' => null,
                                            'customer_name' => null,
                                            'start_date' => null,
                                            'return_date' => now(),
                                            'driver_id' => null,
                                            'last_driver_id' => $eloquentTasks->driver_id,
                                            'vehicle_id' => null
                                        ]);
                                    }
                                }
                            }
                        }
                        // Remove Current Tasks                        

                        foreach ($removalDates as $key => $removalDate) {

                            $subTotal = $rental->removal;
                            $subTotalTax = 0;
                            $grandTotal = $rental->removal;

                            $thisRemoval = Removal::create([
                                'rental_id' => $rental->id,
                                'price_level_rate_id' => $rental->price_level_rate_id,
                                'type' => 0,
                                'removal_type' => 0,
                                'from_date' => $fromDate,
                                'to_date' => $toDate,
                                'perpatual' => $rental->perpatual,
                                'pending_date' => date('Y-m-d H:i:s', strtotime($key == 0 ? $rental->delivery_date : $removalDate)),
                                'code' => $rental->code . sprintf('%06d', $key + 1),
                                'date' => $rental->date,
                                'requested_by' => $rental->requested_by,
                                'customer_id' => $rental->customer_id,
                                'location_id' => $rental->location_id,
                                'purchase_order' => $rental->purchase_order,
                                'status' => 0,
                                'inventory_id' => $rental->inventory_id,
                                'vehicle_id' => $rental->vehicle_id ?? null,
                                'waste_type_id' => $rental->waste_type_id,
                                'instructions' => $rental->instructions,
                                'reference' => $rental->reference,
                                'removal_date' => date('Y-m-d H:i:s', strtotime($removalDate)),
                                'request_method_id' => $rental->request_method_id,
                                'payment_term_id' => $rental->payment_term_id,
                                'tax' => $rental->tax,
                                'flat_rate_billing' => $rental->flat_rate_billing,
                                'total_removal' => $subTotal,
                                'total_tax' => $subTotalTax,
                                'total' => $grandTotal,
                            ]);

                            $thisSkipUnit = InventoryItem::where('inventory_id', $rental->inventory_id)->where('available', 1)->first();

                            $task = Task::create([
                                'code' => Helper::generateTaskNumber(),
                                'legacy_code' => Helper::generateTaskNumber(),
                                'importance' => 0,
                                'service_type' => $rental->contract_type,
                                'customer_id' => $thisRemoval->customer_id,
                                'location_id' => $thisRemoval->location_id,
                                'skip_unit_id' => $thisSkipUnit->id ?? null,
                                'task_skip_unit_id' => $thisSkipUnit->id ?? null,
                                'address' => (isset($rental->location->address) ? $rental->location->address : '') . ', ' . (isset($rental->location->address_2) ? $rental->location->address_2 : '') . ', ' . (isset($rental->location->address_3) ? $rental->location->address_3 : ''),
                                'email' => isset($rental->customer->email) ? $rental->customer->email : '',
                                'latitude' => isset($rental->location->latitude) ? $rental->location->latitude : '',
                                'longitude' => isset($rental->location->longitude) ? $rental->location->longitude : '',
                                'short_description' => $thisRemoval->instructions,
                                'job_type' => 1,

                                'driver_id' => $rental->driver_id,
                                'secondman_id' => null,

                                'task_date' => $thisRemoval->removal_date,
                                'status' => 0,
                                'removal_id' => $thisRemoval->id,
                            ]);

                            if ($thisSkipUnit && $thisSkipUnit->id != null) {
                                InventoryItem::where('id', $thisSkipUnit->id)->update([
                                    'available' => 2,
                                    'task_id' => $task->id,
                                    'customer_id' => $thisRemoval->customer_id,
                                    'customer_name' => ($customerDetails->name ?? ''),
                                    'start_date' => date('Y-m-d H:i:s', strtotime($thisRemoval->removal_date)),
                                    'driver_id' => $rental->driver_id,
                                    'vehicle_id' => $rental->vehicle_id ?? null,
                                    'last_driver_id' => $rental->driver_id
                                ]);
                            }
                        }
                    }


                    \DB::commit();
                } catch (\Exception $e) {
                    \DB::rollBack();

                    \Log::critical('ERROR ON REMOVAL GENERATION FOR RENTAL/SERVICE : ' . $this->recordId . ' ERROR :' . $e->getMessage() . ' LINE NO : ' . $e->getLine());
                }

            } else {
                if ($rental->is_specific_day_frequency == '1') {
                    if (is_countable($select_days) && count($select_days) > 0) {
                        foreach ($select_days as $singleDay) {
                            if (isset($allDates[$singleDay])) {
                                $removalDates[] = Frequency::countWeekSpecificDays($firstParameter, $SecondParameter, $allDates[$singleDay]);
                            }
                        }

                        if (!empty($removalDates)) {
                            $removalDates = \Arr::flatten($removalDates);
                        }
                    }
                } else {
                    if ($frequency_type == '1') {
                        $removalDates = Frequency::getAllDates($firstParameter, $SecondParameter, $freqTime);
                    } else if ($frequency_type == '2') {
                        $removalDates = Frequency::getWeekDates($firstParameter, $SecondParameter, $freqTime);
                    } else if ($frequency_type == '3') {
                        $removalDates = Frequency::getBiweekDates($firstParameter, $SecondParameter, $freqTime);
                    } else if ($frequency_type == '4') {
                        $removalDates = Frequency::getMonthDates($firstParameter, $SecondParameter, $freqTime);
                    } else if ($frequency_type == '5') {
                        $removalDates = Frequency::getBimonthDates($firstParameter, $SecondParameter, $freqTime);
                    } else if ($frequency_type == '6') {
                        $removalDates = Frequency::getQuarterDates($firstParameter, $SecondParameter, $freqTime);
                    } else if ($frequency_type == '7') {
                        $removalDates = Frequency::getSemiAnnualDates($firstParameter, $SecondParameter, $freqTime);
                    } else if ($frequency_type == '8') {
                        $removalDates = Frequency::getAnnualDates($firstParameter, $SecondParameter, $freqTime);
                    }
                }

                $removalDates = collect($removalDates)->values()->toArray();

                \DB::beginTransaction();

                try {
                    if (!empty($removalDates)) {

                        $allCurrentRemovals = Removal::where('rental_id', $rental->id)->whereIn('status', [0, 1])->pluck('skip_unit_id', 'id')->toArray();

                        // Remove Current Tasks
                        if ($this->shouldCancelOtherRemovals) {
                            foreach ($allCurrentRemovals as $id => $theSkipUnitId) {
                                if ($id) {
                                    Removal::find( $id)->delete();
                                    $eloquentTasks = Task::where('removal_id', $id)->first();
                                    if ($eloquentTasks) {
                                        Task::find($eloquentTasks->id)->delete();

                                        InventoryItem::where('id', $theSkipUnitId)->update([
                                            'available' => 1,
                                            'task_id' => null,
                                            'customer_id' => null,
                                            'customer_name' => null,
                                            'start_date' => null,
                                            'return_date' => now(),
                                            'driver_id' => null,
                                            'last_driver_id' => $eloquentTasks->driver_id,
                                            'vehicle_id' => null
                                        ]);
                                    }
                                }
                            }
                        }
                        // Remove Current Tasks                        

                        foreach ($removalDates as $key => $removalDate) {

                            $subTotal = $rental->removal;
                            $subTotalTax = 0;
                            $grandTotal = $rental->removal;

                            $thisRemoval = Removal::create([
                                'rental_id' => $rental->id,
                                'price_level_rate_id' => $rental->price_level_rate_id,
                                'type' => 0,
                                'removal_type' => 0,
                                'from_date' => $fromDate,
                                'to_date' => $toDate,
                                'perpatual' => $rental->perpatual,
                                'pending_date' => date('Y-m-d H:i:s', strtotime($key == 0 ? $rental->delivery_date : $removalDate)),
                                'code' => $rental->code . sprintf('%06d', $key + 1),
                                'date' => $rental->date,
                                'requested_by' => $rental->requested_by,
                                'customer_id' => $rental->customer_id,
                                'location_id' => $rental->location_id,
                                'purchase_order' => $rental->purchase_order,
                                'status' => 0,
                                'inventory_id' => $rental->inventory_id,
                                'vehicle_id' => $rental->vehicle_id ?? null,
                                'waste_type_id' => $rental->waste_type_id,
                                'instructions' => $rental->instructions,
                                'reference' => $rental->reference,
                                'removal_date' => date('Y-m-d H:i:s', strtotime($removalDate)),
                                'request_method_id' => $rental->request_method_id,
                                'payment_term_id' => $rental->payment_term_id,
                                'price_level_id' => $rental->price_level_id,
                                'tax' => $rental->tax,
                                'flat_rate_billing' => $rental->flat_rate_billing,
                                'total_removal' => $subTotal,
                                'total_tax' => $subTotalTax,
                                'total' => $grandTotal,
                            ]);

                            $thisSkipUnit = InventoryItem::where('inventory_id', $rental->inventory_id)->where('available', 1)->first();

                            $task = Task::create([
                                'code' => Helper::generateTaskNumber(),
                                'legacy_code' => Helper::generateTaskNumber(),
                                'importance' => 0,
                                'service_type' => $rental->contract_type,
                                'customer_id' => $thisRemoval->customer_id,
                                'location_id' => $thisRemoval->location_id,
                                'skip_unit_id' => $thisSkipUnit->id ?? null,
                                'task_skip_unit_id' => $thisSkipUnit->id ?? null,
                                'address' => (isset($rental->location->address) ? $rental->location->address : '') . ', ' . (isset($rental->location->address_2) ? $rental->location->address_2 : '') . ', ' . (isset($rental->location->address_3) ? $rental->location->address_3 : ''),
                                'email' => isset($rental->customer->email) ? $rental->customer->email : '',
                                'latitude' => isset($rental->location->latitude) ? $rental->location->latitude : '',
                                'longitude' => isset($rental->location->longitude) ? $rental->location->longitude : '',
                                'short_description' => $thisRemoval->instructions,
                                'job_type' => 1,

                                'driver_id' => $rental->driver_id,
                                'secondman_id' => null,

                                'task_date' => $thisRemoval->removal_date,
                                'status' => 0,
                                'removal_id' => $thisRemoval->id,
                            ]);

                            if ($thisSkipUnit && $thisSkipUnit->id != null) {
                                InventoryItem::where('id', $thisSkipUnit->id)->update([
                                    'available' => 2,
                                    'task_id' => $task->id,
                                    'customer_id' => $thisRemoval->customer_id,
                                    'customer_name' => ($customerDetails->name ?? ''),
                                    'start_date' => date('Y-m-d H:i:s', strtotime($thisRemoval->removal_date)),
                                    'driver_id' => $rental->driver_id,
                                    'vehicle_id' => $rental->vehicle_id ?? null,
                                    'last_driver_id' => $rental->driver_id
                                ]);
                            }
                        }
                    }


                    \DB::commit();
                } catch (\Exception $e) {
                    \DB::rollBack();

                    \Log::critical('ERROR ON REMOVAL GENERATION FOR RENTAL/SERVICE : ' . $this->recordId . ' ERROR :' . $e->getMessage() . ' LINE NO : ' . $e->getLine());
                }
            }
        }
    }
}
