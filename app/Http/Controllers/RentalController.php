<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\PriceLevelRate;
use App\Jobs\GenerateRemovals;
use App\Jobs\GenerateServices;
use Illuminate\Http\Request;
use App\Models\Inventory;
use App\Helpers\Helper;
use App\Models\Vehicle;
use App\Models\Removal;
use App\Models\Rental;
use App\Models\Task;
use App\Models\Tax;

class RentalController extends Controller
{
    public function index(Request $request) {
        if ($request->ajax()) {
            $requestData = $request->all();

            $rentals = Rental::query()
            ->when(!empty($request->rental), function ($builder) use ($requestData) {
                $builder->where('code', 'LIKE', '%' . $requestData['rental'] . '%');
            })
            ->when($request->user > 0, function ($builder) use ($requestData) {
                $builder->where('customer_id', $requestData['user']);
            })
            ->when(!empty($request->from), function ($builder) use ($requestData) {
                $builder->where(\DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d')"), '>=', date('Y-m-d H:i', strtotime($requestData['from'])));
            })
            ->when(!empty($request->to), function ($builder) use ($requestData) {
                $builder->where(\DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d')"), '<=', date('Y-m-d H:i', strtotime($requestData['to'])));
            })
            ->when($request->frequency >= 1, function ($builder) use ($requestData) {
                $builder->where('frequency_type', $requestData['frequency']);
            })
            ->when($request->type == '1', function ($builder) {
                $builder->where('contract_type', '0');
            })
            ->when($request->type == '2', function ($builder) {
                $builder->where('contract_type', '1');
            })
            ->when($request->archived == 2, function ($builder) use ($requestData) {
                if ($requestData['archived'] == 2) {
                    return $builder->onlyTrashed();
                }
            })
            ->when($request->hiring == 'on' || $request->hiring == 'off', function ($builder) use ($requestData) {
                if ($requestData['hiring'] == 'off') {
                    $builder->where('status', 3);
                } else {
                    $builder->whereIn('status', [0, 1, 2]);
                }
            })
            ->orderBy('id', 'DESC');

            return datatables()
            ->eloquent($rentals)
            ->editColumn('customer', function ($row) {
                if (isset($row->customer->id)) {
                    return $row->customer->code . ' - ' . $row->customer->name;
                }

                return '-';
            })
            ->editColumn('from_date', function ($row) {
                return date('d-m-Y', strtotime($row->from_date));
            })
            ->editColumn('to_date', function ($row) {
                return date('d-m-Y', strtotime($row->to_date));
            })
            ->editColumn('date', function ($row) {
                return date('d-m-Y', strtotime($row->date));
            })
            ->editColumn('status', function ($row) {
                $html = '';

                if (Removal::where('rental_id', $row->id)->where('status', 5)->where('removal_type', 4)->exists()) {
                    $html .= '<span class="badge bg-success"> On Hire </span><br>';
                } else {
                    $html .= '<span class="badge bg-warning"> Off Hire </span><br>';
                }

                if ($row->status == '1') {
                    $html .= '<span class="badge bg-danger"> Cancelled </span>';
                }

                return $html;
            })
            ->editColumn('contract_type', function ($row) {
                if ($row->contract_type != 1) {
                    return '<span class="badge bg-primary"> Rental </span>';
                } else {
                    return '<span class="badge bg-secondary"> Service </span>';
                }
            })
            ->addColumn('action', function ($row) {

                $action = '';

                if (!(empty($row->deleted_at) || is_null($row->deleted_at))) {
                    if (auth()->user()->can('rentals.edit')) {
                        $action .= '<form method="POST" action="'.route("rentals.restore", encrypt($row->id)).'" style="display:inline;"><input type="hidden" name="_method" value="POST"><input type="hidden" name="_token" value="'.csrf_token().'"><button type="submit" class="btn btn-info btn-sm restoreGroup">Restore</button></form>';
                    }
    
                    if (auth()->user()->can('rentals.show')) {
                        $action .= '<a href="'.route('rentals.show', encrypt($row->id)).'" class="btn btn-warning btn-sm me-2 btn-view"> <i class="bi bi-eye"></i> </a>';
                    }
                } else {
                    if (auth()->user()->can('rentals.edit') && !in_array($row->status, [1, 3])) {
                        $action .= '<a href="'.route('rentals.edit', encrypt($row->id)).'" class="btn btn-info btn-sm me-2 btn-edit"> <i class="bi bi-pencil-square"></i> </a>';
                    }
    
                    if (auth()->user()->can('rentals.show')) {
                        $action .= '<a href="'.route('rentals.show', encrypt($row->id)).'" class="btn btn-warning btn-sm me-2 btn-view"> <i class="bi bi-eye"></i> </a>';
                    }
    
                    if (auth()->user()->can('rentals.destroy')) {
                        $action .= '<form method="POST" action="'.route("rentals.destroy", encrypt($row->id)).'" style="display:inline;"><input type="hidden" name="_method" value="DELETE"><input type="hidden" name="_token" value="'.csrf_token().'"><button type="submit" class="btn btn-danger btn-sm deleteGroup btn-delete"><i class="bi bi-trash"></i></button></form>';
                    }
                }

                return $action;
            })
            ->rawColumns(['action', 'status', 'contract_type'])
            ->toJson();
        }

        if ($request->has('contract_type')) {
            session()->put(['contract_type' => $request->contract_type]);
            return redirect()->route('rentals.index');
        }

        $page_title = 'Rentals';
        $page_description = 'Manage your rentals here';

        return view('rentals.index', compact('page_title', 'page_description'));
    }

    public function create(Request $request) {
        $page_title = 'Rental Add';
        $rentalNumber = Helper::generateRentalNumber();

        return view('rentals.create', compact('page_title', 'rentalNumber'));
    }

    public function store(Request $request) {

        $this->validate($request, [
            'code' => "required|unique:rentals,code",
            'date' => 'required',
            'customer' => 'required|exists:users,id',
            'location' => 'required|exists:locations,id',
            'request_method' => 'required|exists:request_methods,id',
            'waste_type' => 'required|exists:waste_types,id',
            'company' => 'required|exists:companies,id',
            'pt' => 'required|exists:payment_terms,id',
            'from' => 'required',
            'deliver' => 'required',
            'removal' => 'required',
            'rental_rate' => 'required|min:0',
            'removal_rate' => 'required|min:0',
            'pay_method' => 'required|in:fixed,percentage',
            'driver' => 'required|min:0',
            'second' => 'required|min:0',
            'tax' => 'required|exists:taxes,id',
            'tipping_fee_type' => 'required|in:fixed,weight',
            'tipping_fee' => 'required|min:0',
            'status' => 'required',
            'rental_counter' => 'required',
            'total_rental' => 'required|min:0',
            'total_tax' => 'required|min:0',
            'total' => 'required|min:0',
            'frequency_time' => 'required'
        ]);

        DB::beginTransaction();

        try {
            
            $fromDate = date('Y-m-d H:i:s', strtotime($request->from));

            if (!empty($request->to)) {
                $toDate = date('Y-m-d H:i:s', strtotime($request->to));
            } else {
                $toDate = date('Y-m-d H:i:s', strtotime($request->from . '+1 month'));
            }

            $allDays = $request->frequency_type == '1' ? (is_array($request->select_days) ? implode(',', $request->select_days) : '') : '';
            $contractType = $request->contracttype == '1' ? 0 : 1;
            $isContractFrequency = $request->frequency_contract == '1' ? 1 : 0;

            $inventoryRates = Inventory::find($request->skip);

            $rental = Rental::create([
                'code' => strtoupper($request->code),
                'date' => date('Y-m-d H:i:s', strtotime($request->date)),
                'frequency_contract' => $isContractFrequency,
                'perpatual_generation_date' => $toDate,
                'customer_id' => $request->customer,
                'perpatual' => $isContractFrequency ? ($request->perpatual == '1' ? 1 : 0) : 0,
                'perpetual_2' => 0,
                'contract_type' => $contractType,
                'location_id' => $request->location,
                'requested_by' => $request->request_by,
                'purchase_order' => $request->po,
                'inventory_id' => $request->skip,
                'driver_id' => $request->driver_id,
                'second_man_id' => $request->second_man_id,
                'price_level_rate_id' => $request->price_level_rate_id,
                'vehicle_id' => $request->vehicle,
                'service_type_id' => $request->servicetype,
                'waste_type_id' => $request->waste_type,
                'reference' => $request->reference,
                'rental' => $request->rental_rate,
                'total_rental_rate' => $request->total_rental_rate,
                'removal' => $request->removal_rate,
                'from_date' => $fromDate,
                'real_from_date' => $fromDate,
                'to_date' => $toDate,
                'daily_rental' => $inventoryRates->daily_rental ?? 0,
                'monthly_rental' => $inventoryRates->monthly_rental ?? 0,
                'final_removal' => $inventoryRates->final_removal ?? 0,
                'new_rental' => $inventoryRates->new_rental ?? 0,
                'delivery_date' => date('Y-m-d H:i:s', strtotime($request->deliver)),
                'real_delivery_date' => date('Y-m-d H:i:s', strtotime($request->deliver)),                
                'delivered' => 0,
                'delivery_override' => 0,
                'removal_date' => date('Y-m-d H:i:s', strtotime($request->removal)),
                'removed' => 0,
                'removal_override' => 0,
                'instructions' => $request->instruction,
                'status' => $request->status,
                'request_method_id' => $request->request_method,
                'price_level_id' => $request->price_levels,
                'pay_method' => $request->pay_method,
                'charge_basis' => $isContractFrequency ? (in_array($request->cb, ['daily', 'weekly', 'monthly']) ? strtolower($request->cb) : 'daily') : 'daily',
                'charge_basis_count' => $request->cbc,
                'driver' => $request->driver,
                'second' => $request->second,
                'company_id' => $request->company,
                'payment_term_id' => $request->pt,
                'flat_rate_billing' => $request->flat_rate_check == '1' ? 1 : 0,
                'tax' => $request->tax,
                
                'sub_total' => $request->total_rental,                
                'sub_total_tax' => $request->total_tax,
                'total' => $request->total,

                'total_removals' => $isContractFrequency ? $request->rental_counter : 1,
                'total_rental_contract_days' => $request->days,
                'tipping_fee_type' => $request->tipping_fee_type,
                'tipping_fee' => $request->tipping_fee,
                'is_specific_day_frequency' => $request->frequency_type == '1' ? 1 : 0,
                'days' => $allDays,
                'frequency_type' => $request->frequency_type == '1' ? 1 : $request->frequency,
                'has_zero_total' => $request->has_zero_total == '1' ? 1 : 0,
                'frequency_time' => date('H:i:s', strtotime($request->frequency_time)),
            ]);

            if ($contractType == 0) {
                GenerateRemovals::dispatch($rental->id, 'rental');
            } else {
                GenerateServices::dispatch($rental->id, 'service');
            }

            DB::commit();
            return redirect()->route('rentals.index')->with('success',  $contractType == 0 ? 'Rental created successfully' : 'Service created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("RENTAL/SERVICE CREATION ERROR : " . $e->getMessage() . ' ON LINE NO : ' . $e->getLine());
            return redirect()->back()->with('error', Helper::$errorMessage);
        }

    }

    public function edit(Request $request, $id) {
        $page_title = 'Rental Edit';
        $rental = Rental::find(decrypt($id));
        $newRentalCompleted = Removal::where('rental_id', $rental->id)->where('removal_type', 4)->whereNotIn('status', [0, 2])->exists();
        $firstRemovalCompleted = Removal::where('rental_id', $rental->id)->whereIn('removal_type', [1, 3])->whereNotIn('status', [0, 2])->exists();

        return view('rentals.edit', compact('page_title', 'rental', 'id', 'newRentalCompleted', 'firstRemovalCompleted'));
    }

    public function update(Request $request, $id) {

        $id = decrypt($id);

        $this->validate($request, [
            'request_method' => 'required|exists:request_methods,id',
            'waste_type' => 'required|exists:waste_types,id',
            'company' => 'required|exists:companies,id',
            'pt' => 'required|exists:payment_terms,id',
            'from' => 'required',
            'deliver' => 'required',
            'removal' => 'required',
            'rental_rate' => 'required|min:0',
            'removal_rate' => 'required|min:0',
            'pay_method' => 'required|in:fixed,percentage',
            'driver' => 'required|min:0',
            'second' => 'required|min:0',
            'tax' => 'required|exists:taxes,id',
            'tipping_fee_type' => 'required|in:fixed,weight',
            'tipping_fee' => 'required|min:0',
            'status' => 'required',
            'rental_counter' => 'required',
            'total_rental' => 'required|min:0',
            'total_tax' => 'required|min:0',
            'total' => 'required|min:0',
            'frequency_time' => 'required'
        ]);

        $shouldDispatchJob = false;

        DB::beginTransaction();

        try {
                $theRental = Rental::find($id);
            
                $allDays = $request->frequency_type == '1' ? (is_array($request->select_days) ? implode(',', $request->select_days) : '') : '';
                $fromDate = date('Y-m-d H:i:s', strtotime($request->from));
                $toDate = date('Y-m-d H:i:s', strtotime($request->to));
                $deliverDate = date('Y-m-d H:i:s', strtotime($request->deliver));
                $removalDate = date('Y-m-d H:i:s', strtotime($request->removal));
                $inventoryRates = Inventory::find($request->skip);
                $isContractFrequency = $request->frequency_contract == '1' ? 1 : 0;
            
                if ($theRental->from_date != $fromDate || $theRental->to_date != $toDate || 
                    $theRental->delivery_date != $deliverDate || $theRental->removal_date != $removalDate ||
                    $theRental->total_removals != $request->rental_counter ||  $theRental->days != $allDays ||
                    $theRental->price_level_id != $request->price_levels
                ) {
                    $shouldDispatchJob = true;
                }

                $theRental->requested_by = $request->request_by;
                $theRental->purchase_order = $request->po;

                if ($isContractFrequency) {
                    if ($theRental->perpatual == '0' && $request->perpatual == '1') {
                        $theRental->perpatual = 1;
                        $theRental->perpatual_generation_date = date('Y-m-d H:i:s', strtotime('+1 month'));
                    } else if ($theRental->perpatual == '1' && $request->perpatual == '0') {
                        $theRental->perpatual = 0;
                        $theRental->perpatual_generation_date = now();
                    }
                } else {
                    $theRental->perpatual = 0;
                    $theRental->perpatual_generation_date = null;                    
                }

                $theRental->frequency_contract = $isContractFrequency;
                $theRental->company_id = $request->company;
                $theRental->inventory_id = $request->skip;
                $theRental->driver_id = $request->driver_id;
                $theRental->vehicle_id = $request->vehicle;

                $theRental->has_zero_total = $request->has_zero_total == '1' ? 1 : 0;

                $theRental->service_type_id = $request->servicetype;
                $theRental->waste_type_id = $request->waste_type;
                $theRental->reference = $request->reference;
                $theRental->rental = $request->rental_rate;
                $theRental->total_rental_rate = $request->total_rental_rate;
                $theRental->removal = $request->removal_rate;

                if ($theRental->delivery_date != date('Y-m-d H:i:s', strtotime($request->deliver))) {
                    $theRental->delivery_override = 1;
                    $theRental->delivery_date = date('Y-m-d H:i:s', strtotime($request->deliver));
                }

                if ($theRental->removal_date != date('Y-m-d H:i:s', strtotime($request->removal))) {
                    $theRental->removal_override = 1;
                    $theRental->removal_date = date('Y-m-d H:i:s', strtotime($request->removal));
                }

                $theRental->from_date = $fromDate;
                $theRental->to_date = $toDate;
                $theRental->delivery_date = $deliverDate;
                $theRental->removal_date = $removalDate;

                $theRental->daily_rental = $inventoryRates->daily_rental ?? 0;
                $theRental->monthly_rental = $inventoryRates->monthly_rental ?? 0;
                $theRental->final_removal = $inventoryRates->final_removal ?? 0;
                $theRental->new_rental = $inventoryRates->new_rental ?? 0;

                $theRental->instructions = $request->instruction;
                $theRental->status = $request->status;
                $theRental->request_method_id = $request->request_method;
                $theRental->price_level_id = $request->price_levels;
                $theRental->pay_method = $request->pay_method;
                $theRental->charge_basis = $isContractFrequency ? (in_array($request->cb, ['daily', 'weekly', 'monthly']) ? strtolower($request->cb) : 'daily') : 'daily';
                $theRental->charge_basis_count = $request->cbc;
                $theRental->driver = $request->driver;
                $theRental->second = $request->second;
                $theRental->price_level_rate_id = $request->price_level_rate_id;
                $theRental->payment_term_id = $request->pt;
                $theRental->flat_rate_billing = $request->flat_rate_check == '1' ? 1 : 0;
                $theRental->tax = $request->tax;

                $theRental->sub_total = $request->total_rental;
                $theRental->sub_total_tax = $request->total_tax;
                $theRental->total = $request->total;
                
                $theRental->total_removals = $isContractFrequency ? $request->rental_counter : 1;
                $theRental->total_rental_contract_days = $request->days;
                $theRental->tipping_fee = $request->tipping_fee;
                $theRental->tipping_fee_type = $request->tipping_fee_type;
                
                $theRental->is_specific_day_frequency = $request->frequency_type == '1' ? 1 : 0;
                $theRental->days = $allDays;
                $theRental->frequency_type = $request->frequency_type == '1' ? 1 : $request->frequency;
                $theRental->frequency_time = date('H:i:s', strtotime($request->frequency_time));

                $theRental->save();

                if ($shouldDispatchJob) {
                    if ($theRental->contract_type == 0) {
                        GenerateRemovals::dispatch($theRental->id, 'rental', true);
                    } else {
                        GenerateServices::dispatch($theRental->id, 'service', true);
                    }
                }

            DB::commit();
            return redirect()->route('rentals.index')->with('success',  $theRental->contract_type == 0 ? 'Rental updated successfully' : 'Service updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("RENTAL CREATION ERROR : " . $e->getMessage() . ' ON LINE NO : ' . $e->getLine());
            return redirect()->back()->with('error', Helper::$errorMessage);
        }

    }

    public function destroy($id) 
    {
        DB::beginTransaction();

        try {
            $id = decrypt($id);
            $rental = Rental::find($id);
            $removals = Removal::where('rental_id', $id)->where('status', 0)->pluck('id')->toArray();

            foreach ($removals as $removal) {
                Removal::where('id', $removal)->where('status', 0)->update([
                    'deleted_at' => now(),
                    'restorable' => 0
                ]);

                $eloquentTasks = Task::where('removal_id', $removal)->where('status', 0)->first();
                if ($eloquentTasks) {
                    Task::find($eloquentTasks->id)->update([
                        'deleted_at' => now(),
                        'restorable' => 0
                    ]);
                }
            }

            $rental->update([
                'deleted_at' => now(),
                'restorable' => 0
            ]);

            DB::commit();
            return redirect()->route('rentals.index')->with('success', __('Rental deleted successfully.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('rentals.index')->with('error', Helper::$errorMessage);
        }    
    }

    public function restore($id) {
        DB::beginTransaction();

        try {
            $id = decrypt($id);
            Rental::onlyTrashed()->where('id', $id)->update([
                'deleted_at' => null,
                'restorable' => 1
            ]);
            $removals = Removal::onlyTrashed()->where('rental_id', $id)->where('status', 0)->pluck('id')->toArray();

            foreach ($removals as $removal) {
                Removal::onlyTrashed()->where('id', $removal)->where('status', 0)->update([
                    'deleted_at' => null,
                    'restorable' => 1
                ]);
                Task::onlyTrashed()->where('removal_id', $removal)->where('status', 0)->update([
                    'deleted_at' => null,
                    'restorable' => 1
                ]);
            }

            DB::commit();
            return redirect()->route('rentals.index')->with('success', __('Rental restored successfully.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('rentals.index')->with('error', Helper::$errorMessage);
        } 
    }

    public function show(Request $request, $id) {

        $rental = Rental::withTrashed()->find(decrypt($id));
        $page_title = 'Rental show';

        return view('rentals.show', ['rental' => $rental, 'page_title' => $page_title]);
    }

    public function getDates(Request $request) {
        return response()->json(['status' => true]);
    }

    public function getRentalTaxJson(Request $request) {
        $queryString = trim($request->searchQuery);
        $excludeId = $request->id;
        $page = $request->input('page', 1);
        $limit = env('SELECT2_PAGE_LENGTH', 5);
        $getAllData = $request->all;
        $customer = $request->customer_id;
    
        $query = Tax::when(is_numeric($excludeId) && $excludeId != '0', function ($builder) use ($excludeId) { 
            return $builder->where('id', '!=', $excludeId);
        });
    
        if (!empty($queryString)) {
            $query = $query->where(function ($builder) use ($queryString) {
                return $builder->where('code', 'LIKE', "%{$queryString}%")
                ->orWhere('description', 'LIKE', "%{$queryString}%");
            });
        }
    
        $data = $query->paginate($limit, ['*'], 'page', $page);
    
        return response()->json([
            'items' => $data->map(function ($pro) use ($getAllData) {
                $toBeReturned = [
                    'id' => $pro->id,
                    'text' => $pro->code . ' - ' . $pro->description
                ];

                if ($getAllData == 1) {
                    $toBeReturned['rate'] = $pro->rate;
                }

                return $toBeReturned;
            }),
            'pagination' => [
                'more' => $data->hasMorePages()
            ]
        ]);
    }

    public function get(Request $request)
    {
        $queryString = trim($request->searchQuery);
        $page = $request->input('page', 1);
        $limit = env('SELECT2_PAGE_LENGTH', 5);
        $withTrashed = $request->withTrashed;
    
        $query = Rental::whereHas('removals', function ($builder) {
            return $builder->where('id', '>', 0);
        });
    
        if (!empty($queryString)) {
            $query->where(function ($builder) use ($queryString) {
                return $builder->where('code', 'LIKE', "%{$queryString}%");
            });
        }

        if ($withTrashed == 1) {
            $query = $query->withTrashed();
        }
    
        $data = $query->paginate($limit, ['*'], 'page', $page);
    
        return response()->json([
            'items' => $data->map(function ($pro) {
                return [
                    'id' => $pro->id,
                    'text' => $pro->code
                ];
            }),
            'pagination' => [
                'more' => $data->hasMorePages()
            ]
        ]);
    }

    public function getActiveRentals(Request $request)
    {
        $queryString = trim($request->searchQuery);
        $page = $request->input('page', 1);
        $limit = env('SELECT2_PAGE_LENGTH', 5);
        $withTrashed = $request->withTrashed;
    
        $query = Rental::with(['customer' => function ($builder) {
            return $builder->withTrashed();
        }])->whereIn('status', [0, 2])
        ->whereDoesntHave('removals', function ($builder) {
            $builder->whereIn('removal_type', [2, 3])
            ->whereIn('status', [5]);
        });

        if (!empty($queryString)) {
            $query
            ->where(function ($innerBuilder) use ($queryString) {
                $innerBuilder->where('code', 'LIKE', "%{$queryString}%")
                ->orWhere('from_date', 'LIKE', "%{$queryString}%")
                ->orWhereHas('customer', function ($builder) use ($queryString) {
                    $builder->where('name', 'LIKE', "%{$queryString}%");
                })
                ->orWhereHas('customer', function ($builder) use ($queryString) {
                    $builder->where('code', 'LIKE', "%{$queryString}%");
                })
                ->orWhereHas('location', function ($builder) use ($queryString) {
                    $builder->where('address', 'LIKE', "%{$queryString}%");
                })
                ->orWhereHas('location', function ($builder) use ($queryString) {
                    $builder->where('address_2', 'LIKE', "%{$queryString}%");
                })
                ->orWhereHas('location', function ($builder) use ($queryString) {
                    $builder->where('address_3', 'LIKE', "%{$queryString}%");
                })
                ->orWhereHas('inventory', function ($builder) use ($queryString) {
                    $builder->where('title', 'LIKE', "%{$queryString}%");
                });

                if (strpos(strtolower(strtolower($queryString)), 'perpetual') !== false) {
                    $innerBuilder->orWhere('perpatual', 1);
                }
            });
        }

        if ($withTrashed == 1) {
            $query = $query->withTrashed();
        }
    
        $data = $query->paginate($limit, ['*'], 'page', $page);
    
        return response()->json([
            'items' => $data->map(function ($pro) {
                return [
                    'id' => $pro->id,
                    'text' => $pro->code . ' - ' . ($pro->customer->name ?? '') . ' - ' . (isset($pro->location->id) && !empty($pro->location->address) ? ($pro->location->address . ' ' . $pro->location->address_2 . ' ' . $pro->location->address_3) : '') . ' - ' . (isset($pro->inventory->id) ? $pro->inventory->title : '') . ' - ' . (date('Y-m-d', strtotime($pro->from_date))) . ' - ' . (($pro->perpatual == 1) ? 'Perpetual' : date('Y-m-d', strtotime($pro->to_date))),
                    'from_date' => date('d-m-Y H:i', strtotime($pro->from_date)),
                    'to_date' => date('d-m-Y H:i', strtotime($pro->to_date))
                ];
            }),
            'pagination' => [
                'more' => $data->hasMorePages()
            ]
        ]);
    }

    public function getPriceLevelRate(Request $request) {
        $priceLevelRate = PriceLevelRate::where('inventory_id', $request->inventory_id)
        ->where('price_level_id', $request->price_level_id)
        ->first();

        if ($priceLevelRate) {
            return response()->json(['status' => true, 'data' => [
                'id' => $priceLevelRate->id,
                'type' => 'price_level_rate',
                'rate' => number_format($priceLevelRate->removal_rate, 2)
            ]]);
        }

        $inventory = Inventory::find($request->inventory_id);

        if ($inventory) {
            return response()->json(['status' => true, 'data' => [
                'type' => 'inventory',
                'rate' => number_format($inventory->removal, 2)
            ]]);
        }

        return response()->json(['status' => true, 'data' => [
            'type' => 'zero',
            'rate' => number_format(0, 2)
        ]]);
    }
}
