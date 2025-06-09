<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Models\Removal;
use App\Models\Task;

class TaskController extends Controller
{
    public function index(Request $request)
    {   
        if ($request->ajax()) {

            $requestData = $request->all();
            $tasks = Task::with('invoiceitem')
            ->when($request->type != 'all' && $request->type != 'invoiced' && in_array($request->type, range(0, 5)), function ($builder) use ($requestData) {
                return $builder->whereHas('removal', function ($innerBuilder) use ($requestData) {
                    return $innerBuilder->where('status', $requestData['type']);
                })->whereDoesntHave('invoiceitem');
            })
            ->when(!empty($request->code), function ($builder) use ($requestData) {
                return $builder->where('code', 'LIKE', '%' . $requestData['code'] . '%')
                ->orWhere('legacy_code', 'LIKE', '%' . $requestData['code'] . '%');
            })
            ->when(!empty($request->customer), function ($builder) use ($requestData) {
                return $builder->where('customer_id', $requestData['customer']);
            })
            ->when(!empty($request->driver), function ($builder) use ($requestData) {
                return $builder->where('driver_id', $requestData['driver']);
            })
            ->when($request->jobtype === '0' || $request->jobtype === '1' || $request->jobtype === '2', function ($builder) use ($requestData) {
                $builder->whereHas('removal', function ($insideBuilder) {
                    if (request('jobtype') == '0') {
                        return $insideBuilder->whereIn('removal_type', [4])->orWhere('type', 1);
                    } else if (request('jobtype') == '1') {
                        return $insideBuilder->whereIn('removal_type', [0]);
                    } else if (request('jobtype') == '2') {
                        return $insideBuilder->whereIn('removal_type', [2, 3]);
                    }
                });
            })
            ->when(!empty($request->from), function ($builder) use ($requestData) {
                $fromDate = date('Y-m-d', strtotime($requestData['from']));
                return $builder->where(\DB::raw("DATE_FORMAT(task_date, '%Y-%m-%d')"), '>=', $fromDate);
            })
            ->when(!empty($request->to), function ($builder) use ($requestData) {
                $toDate = date('Y-m-d', strtotime($requestData['to']));
                return $builder->where(\DB::raw("DATE_FORMAT(task_date, '%Y-%m-%d')"), '<=', $toDate);
            })
            ->when($request->archived == 2, function ($builder) use ($requestData) {
                if ($requestData['archived'] == 2) {
                    return $builder->onlyTrashed();
                }
            });

            if ($request->type == 'invoiced') {
                $tasks->whereHas('invoiceitem', function ($builder) {
                    $builder->where('id', '>', 0);
                });
            }

            return datatables()
            ->eloquent($tasks->orderBy('order')->orderBy('task_date', 'ASC'))
            ->editColumn('customer_id', function ($row) {
                if (isset($row->customer->id)) {
                    return $row->customer->name;
                }

                return '-';
            })
            ->editColumn('address', function ($row) {
                if (isset($row->location->id)) {

                    $addressContent = '<strong> Address : </strong> ' . $row->location->address . ' </br>
                    <strong> Contact : </strong> ' . $row->location->contact . ' </br>
                    <strong> Work Telephone : </strong> ' . $row->location->work_telephone . ' </br>
                    <strong> Email : </strong> ' . $row->location->email . ' </br>
                    <strong> Work Telephone : </strong> ' . $row->location->home_telephone . ' </br>
                    <strong> Mobile : </strong> ' . $row->location->mobile . ' </br>
                    <strong> Direction : </strong> ' . $row->location->description . ' </br>';

                    return '<div title="' . $row->location->code . '" data-bs-html="true" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-content="' . $addressContent . '" > ' . ( \Str::limit($row->location->address, 50, '...') ) . ' </div>';
                }

                return '<div  title=' . ($row->address) . '> ' . ( \Str::limit($row->address, 50, '...') ) . ' </div>';
            })
            ->editColumn('task_date', function ($row) {
                if (!empty($row->task_date)) {
                    return date('d-m-Y H:i', strtotime($row->task_date));
                }

                return '-';
            })
            ->editColumn('job_completed_at', function ($row) {
                if ($row->status == 5) {
                    if (!empty($row->job_completed_at)) {
                        return date('d-m-Y H:i', strtotime($row->job_completed_at));
                    }
                }

                return '-';
            })
            ->editColumn('driver_id', function ($row) {
                if (!empty($row->driver->id)) {
                    return $row->driver->name;
                }

                return '-';
            })
            ->addColumn('action', function ($row) {
                $action = '';

                if (!(empty($row->deleted_at) || is_null($row->deleted_at))) {
                    if (auth()->user()->can('tasks.edit')) {
                        $action .= '<form method="POST" action="'.route("tasks.restore", encrypt($row->id)).'" style="display:inline;"><input type="hidden" name="_method" value="POST"><input type="hidden" name="_token" value="'.csrf_token().'"><button type="submit" class="btn btn-info btn-sm restoreGroup" title="Restore"><i class="fa fa-recycle"> </i></button></form>';
                    }
                    
                    if (auth()->user()->can('tasks.show')) {
                        $action .= '<a href="'.route("tasks.show", encrypt($row->id)).'" class="btn btn-warning btn-sm me-2 btn-view" title="Show"> <i class="bi bi-eye"></i> </a>';
                    }
                } else {

                    if (auth()->user()->can('tasks.show')) {
                        $action .= '<a href="'.route("tasks.show", encrypt($row->id)).'" class="btn btn-warning btn-sm me-2 btn-view" title="Show"> <i class="bi bi-eye"></i> </a>';
                    }
    
                    if (auth()->user()->can('tasks.edit') && !in_array($row->status, [2, 5])) {
                        $action .= '<a href="'.route('tasks.edit', encrypt($row->id)).'" class="btn btn-info btn-sm me-2 btn-edit" title="Edit"> <i class="bi bi-pencil-square"></i> </a>';
                    }
    
                    if (auth()->user()->can('tasks.destroy') && !isset($row->invoiceitem->id)) {
                        $action .= '<form method="POST" action="'.route("tasks.destroy", encrypt($row->id)).'" style="display:inline;" class="me-2"><input type="hidden" name="_method" value="DELETE"><input type="hidden" name="_token" value="'.csrf_token().'"><button type="submit" class="btn btn-delete btn-danger btn-sm deleteGroup" title="Delete"> <i class="bi bi-trash"></i> </button></form>';
                    }
                }

                if (auth()->user()->can('tasks.edit') && !isset($row->invoiceitem->id) && !in_array($row->status, [2, 5])) {
                    if ($row->importance) {
                        $action .= '&nbsp;<form method="POST" action="'.route("tasks.unimportant", encrypt($row->id)).'" style="display:inline;"><input type="hidden" name="_token" value="'.csrf_token().'"><button type="submit" class="btn btn-danger btn-sm unimportantGroup btn-import-export" title="Un-Important"> <i class="bi bi-download"></i> </button></form>';
                    } else {
                        $action .= '&nbsp;<form method="POST" action="'.route("tasks.important", encrypt($row->id)).'" style="display:inline;"><input type="hidden" name="_token" value="'.csrf_token().'"><button type="submit" class="btn btn-success btn-sm importantGroup btn-import-export" title="Important"> <i class="bi bi-upload"></i> </button></form>';
                    }
                }

                return $action;
            })
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="checkbox-20 single-check" data-id="' . $row->id . '" />';
            })
            ->addColumn('approve_single', function ($row) {
                if ($row->is_approved != '1') {
                    return '<img data-rid="' . $row->id . '" src="' . asset('assets/images/not-approved.png') . '" style="height:30px;cursor:pointer;" class="approve-single-record" />';
                }

                return '';
            })
            ->editColumn('is_approved', function ($row) {
                if ($row->is_approved == '1') {
                    return '<img style="height:30px;" src="' . asset('assets/images/approved.png') . '" />';
                }

                return '-';
            })
            ->editColumn('legacy_code', function ($row) {
                $html = "<span style='display:flex;'>{$row->legacy_code}</span>";

                if ($row->removal) {
                    if ($row->service_type != 1) {
                        if ($row->removal->type == 1 || $row->removal->removal_type == 4) {
                            $html .= '  <div class="deliver-label badge bg-primary">New Rental</div>';
                        } else if (in_array($row->removal->removal_type, [2, 3])) {
                            $html .= '  <div class="removal-label badge bg-danger">Final Removal</div>';
                        }    
                    } else {
                            $html .= '  <div class="removal-label badge bg-info">Service</div>';                        
                    }
                }

                if ($row->importance) {
                    $html .= ' <div class="important-label badge bg-success mt-2">Important</div>';
                }

                return $html;
            })
            ->editColumn('status', function ($row) {
                if ($row->status == '0') {
                    return '<span class="badge bg-primary"> Ordered </span>';
                } else if ($row->status == '1') {
                    return '<span class="badge bg-warning"> Pending </span>';
                } else if ($row->status == '2') {
                    return '<span class="badge bg-danger"> Cancelled </span>';
                } else if ($row->status == '3') {
                    return '<span class="badge bg-secondary"> On Hold </span>';
                } else if ($row->status == '4') {
                    return '<span class="badge bg-primary"> In Progress </span>';
                } else if ($row->status == '5') {
                    if (isset($row->invoiceitem->id)) {
                        return '<span class="badge bg-info"> Invoiced </span>';
                    } else {
                        return '<span class="badge bg-success"> Completed </span>';
                    }
                }

                return '-';
            })
            ->addColumn('rental_code', function ($row) {
                return $row->removal->rental->code ?? '-';
            })
            ->rawColumns(['action', 'checkbox', 'approve_single', 'is_approved', 'address', 'legacy_code', 'status'])
            ->toJson();
        }

        if ($request->has('dashboard_task_status')) {
            session()->put(['dashboard_task_status' => $request->dashboard_task_status]);
        }

        if ($request->has('dashboard_job_type')) {
            session()->put(['dashboard_job_type' => $request->dashboard_job_type]);
        }

        if ($request->has('dashboard_task_status') || $request->has('dashboard_job_type')) {
            return redirect()->route('tasks.index');
        }

        $page_title = 'Tasks';
        $page_description = 'Manage tasks here';
        return view('tasks.index',compact('page_title', 'page_description'));
    }

    public function approveBulkStatus(Request $request) {
        $ids = explode(',', $request->idString);
        $ids = array_filter($ids);
        $action = $request->action;

        if (!in_array($action, [0, 1, 2, 3, 4, 5])) {
            return response()->json(['status' => false, 'message' => Helper::$errorMessage]);
        }

        \DB::beginTransaction();

        try {
            if (count($ids) > 0) {
                $ids = array_chunk($ids, 200);
                foreach ($ids as $id) {
    
                    if ($action == 0) {
                        $allRemovalsIds = Task::select('removal_id')->where('status', 0)->whereIn('id', $id)->pluck('removal_id')->toArray();
                        $allRemovalsIds = array_filter($allRemovalsIds);
                        $updateStatus = ['status' => 1, 'is_approved' => 1, 'job_started_at' => null, 'job_completed_at' => null];
                        
                        Removal::whereIn('id', $allRemovalsIds)->update(['status' => 1, 'is_approved' => 1]);
                        $eloquentTasks = Task::whereIn('id', $id)->where('status', 0)->get();

                        foreach ($eloquentTasks as $eachTask) {
                            Task::find($eachTask->id)->update($updateStatus);
                        }

                    } else {
                        $allRemovalsIds = Task::select('removal_id')->whereIn('id', $id)->pluck('removal_id')->toArray();
                        $allRemovalsIds = array_filter($allRemovalsIds);
                        $updateStatus = ['status' => $action];
    
                        if ($action == 4) {
                            $updateStatus['job_started_at'] = now();
                            $updateStatus['job_completed_at'] = null;
                        } else if ($action == 5) {
                            $updateStatus['job_completed_at'] = now();
                        } else if ($action == 2) {
                            $updateStatus['job_completed_at'] = null;
                        } else if ($action == 1) {
                            $updateStatus['job_started_at'] = null;
                            $updateStatus['job_completed_at'] = null;
                        }
        
                        Removal::whereIn('id', $allRemovalsIds)->update(['status' => $action]);
                        $eloquentTasks = Task::whereIn('id', $id)->get();
    
                        foreach ($eloquentTasks as $eachTask) {
                            Task::find($eachTask->id)->update($updateStatus);
                        }

                        if (in_array($action, [2, 5])) {
                            foreach ($id as $thisTaskId) {
                                $thisInventoryItem = InventoryItem::where('task_id', $thisTaskId)->first();
                                if ($thisInventoryItem) {
                                    $thisInventoryItem->update([
                                        'available' => 1,
                                        'task_id' => null,
                                        'customer_id' => null,
                                        'customer_name' => null,
                                        'start_date' => null,
                                        'return_date' => now(),
                                        'driver_id' => null,
                                        'vehicle_id' => null,
                                        'last_driver_id' => $thisInventoryItem->last_driver_id ?? null
                                    ]);

                                    if ($action == 5) {
                                        $task = Task::find($thisTaskId);
                                        $tempRemoval = Removal::where('id', $task->removal_id ?? null)->first();
                                        if ($tempRemoval) {

                                            $tempRental = \App\Models\Rental::find($tempRemoval->rental_id);                                            
                                            if ($tempRemoval->removal_type == 4) {
                                                \App\Models\Rental::find($tempRemoval->rental_id)->update(['from_date' => Helper::minDate(date('Y-m-d H:i:s', strtotime($tempRental->real_delivery_date)), date('Y-m-d H:i:s')), 'delivery_date' => Helper::minDate(date('Y-m-d H:i:s', strtotime($tempRental->real_delivery_date)), date('Y-m-d H:i:s'))]);
                                            }

                                            if ($tempRental && $tempRental->perpetual_2 == 1) {
                                                $tempRental->perpatual = 0;
                                                $tempRental->save();
                                            }
                                        }              
                                    }

                                }
                            }
                        }
                    }
                }
            }
    
            \DB::commit();
            return response()->json(['status' => true, 'message' => 'Status updated successfully']);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['status' => false, 'message' => Helper::$errorMessage]);
        }
    }

    public function reassignDriverSecondMan(Request $request) {
        $ids = explode(',', $request->idString);
        $ids = array_filter($ids);
        $driver = $request->driver;
        $sman = $request->sman;

        if (empty($driver) || empty($sman)) {
            return response()->json(['status' => false, 'message' => Helper::$errorMessage]);
        }

        \DB::beginTransaction();

        try {
            if (count($ids) > 0) {
                $ids = array_chunk($ids, 200);
                foreach ($ids as $id) {
                    Task::whereIn('id', $id)->update(['driver_id' => $driver, 'secondman_id' => $sman]);
                }
            }
    
            \DB::commit();
            return response()->json(['status' => true, 'message' => 'Driver/Second-man reassigned successfully']);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['status' => false, 'message' => Helper::$errorMessage]);
        }
    }

    public function reassignVehicle(Request $request) {
        $ids = explode(',', $request->idString);
        $ids = array_filter($ids);
        $vehicle = $request->vehicle;

        if (empty($vehicle)) {
            return response()->json(['status' => false, 'message' => Helper::$errorMessage]);
        }

        \DB::beginTransaction();

        try {
            if (count($ids) > 0) {
                $ids = array_chunk($ids, 200);
                foreach ($ids as $id) {
                    Task::whereIn('id', $id)->update(['vehicle_id' => $vehicle]);
                    Removal::whereHas('tsk', function ($builder) use ($id) {
                        $builder->whereIn('id', $id);
                    })->update(['vehicle_id' => $vehicle]);
                }
            }
    
            \DB::commit();
            return response()->json(['status' => true, 'message' => 'Vehicle reassigned successfully']);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['status' => false, 'message' => Helper::$errorMessage]);
        }
    }

    public function create()
    {
        $page_title = 'Task Add';
        $taskCode = Helper::generateTaskNumber();

        return view('tasks.create', compact( 'page_title', 'taskCode'));
    }
    
    public function store(Request $request)
    {    
        $this->validate($request, [
            'code' => ['required', function ($name, $value, $fail){
                if (Task::where(\DB::raw('LOWER(code)'), strtolower($value))->exists()) {
                    $fail("Code with this name is already exists.");
                }
            }],
            'customer' => 'required',
            'date' => 'required',
            'email' => 'required',
            'address' => 'required',
            'skip' => 'required',
            'driver' => 'required'
        ]);

        $task = new Task();
        $task->code = $request->code;
        $task->customer_id = $request->customer;
        $task->email = $request->email;
        $task->location_id = $request->location;
        $task->address = $request->address;
        $task->latitude = $request->lat;
        $task->longitude = $request->long;
        $task->task_date = date('Y-m-d H:i:s', strtotime($request->date));
        $task->skip_unit_id = $request->skip;
        $task->task_skip_unit_id = $request->taskskip;
        $task->short_description = $request->shortdesc;
        $task->description = $request->desc;
        $task->driver_id = $request->driver;
        $task->secondman_id = $request->secondman;

        if (!empty($request->jobdate)) {
            $task->job_started_at = date('Y-m-d H:i:s', strtotime($request->jobdate));
        }

        $task->status = 0;
        $task->disposal_site_id = $request->dispsite ?? null;

        if (!empty($request->dispdate)) {
            $task->disposal_at = date('Y-m-d H:i:s', strtotime($request->dispdate));
        }

        $task->acknowledge_by = $request->ackby;

        if (!empty($request->ackdate)) {
            $task->acknowledged_at = date('Y-m-d H:i:s', strtotime($request->ackdate));
        }

        $task->save();

        $customerDetails = \App\Models\User::where('id', $request->customer)->first();

        InventoryItem::where('id', $request->skip)->update([
            'available' => 2,
            'task_id' => $task->id,
            'customer_id' => $request->customer,
            'customer_name' => ($customerDetails->name ?? ''),
            'start_date' => date('Y-m-d H:i:s', strtotime($request->date)),
            'return_date' => date('Y-m-d H:i:s', strtotime($request->dispdate)),
            'driver_id' => $request->driver,
            'vehicle_id' => $request->vehicle
        ]);

        return redirect()->route('tasks.index')->with('success','Task created successfully');
    }

    public function show($id)
    {
        $page_title = 'Task Show';
        $task = Task::withTrashed()->find(decrypt($id));
    
        return view('tasks.show', compact('task', 'page_title'));
    }

    public function edit($id)
    {
        $page_title = 'Task Edit';
        $task = Task::find(decrypt($id));
    
        return view('tasks.edit', compact('task', 'page_title', 'id'));
    }
    
    public function update(Request $request, $id)
    {
        \DB::beginTransaction();

        try {
            $tId = decrypt($id);

            $this->validate($request, [
                'code' => ['required', function ($name, $value, $fail) use ($tId) {
                    if (Task::where('id', '!=', $tId)->where(\DB::raw('LOWER(code)'), strtolower($value))->exists()) {
                        $fail("Code with this name is already exists.");
                    }
                }],
                'customer' => 'required',
                'status' => ['required'],
                'date' => 'required',
                'email' => 'required',
                'address' => 'required',
                'skip' => 'required',
                'driver' => 'required'
            ]);
    
            $task = Task::find($tId);
    
            $customerDetails = \App\Models\User::where('id', $request->customer)->first();

            Removal::where('id', $task->removal_id)->update([
                'status' => intval($request->status)
            ]);

            InventoryItem::where('id', $task->skip_unit_id)->update([
                'available' => 1,
                'task_id' => null,
                'customer_id' => null,
                'customer_name' => null,
                'start_date' => null,
                'return_date' => now(),
                'driver_id' => null,
                'vehicle_id' => null,
                'last_driver_id' => $task->driver_id ?? null
            ]);
    
            if (!($request->status == '2' || $request->status == '5')) {
                InventoryItem::where('id', $request->skip)->update([
                    'available' => 2,
                    'task_id' => $task->id,
                    'customer_id' => $request->customer,
                    'customer_name' => ($customerDetails->name ?? ''),
                    'start_date' => date('Y-m-d H:i:s', strtotime($request->date)),
                    'return_date' => date('Y-m-d H:i:s', strtotime($request->dispdate)),
                    'driver_id' => $request->driver,
                    'vehicle_id' => $request->vehicle,
                ]);
            }
    
            $task->code = $request->code;
            $task->status = intval($request->status);
            $task->customer_id = $request->customer;
            $task->email = $request->email;
            $task->location_id = $request->location;
            $task->address = $request->address;
            $task->task_date = date('Y-m-d H:i:s', strtotime($request->date));
            $task->skip_unit_id = $request->skip;
            $task->task_skip_unit_id = $request->taskskip;
            $task->short_description = $request->shortdesc;
            $task->description = $request->desc;
            $task->load = $request->load;
            $task->driver_id = $request->driver;
            $task->secondman_id = $request->secondman;
            $task->vehicle_id = $request->vehicle;
    
            if (!empty($request->jobdate)) {
                $task->job_started_at = date('Y-m-d H:i:s', strtotime($request->jobdate));
            }
    
            $task->disposal_site_id = $request->dispsite ?? null;
    
            if (!empty($request->dispdate)) {
                $task->disposal_at = date('Y-m-d H:i:s', strtotime($request->dispdate));
            }
    
            $task->acknowledge_by = $request->ackby;
    
            if (!empty($request->ackdate)) {
                $task->acknowledged_at = date('Y-m-d H:i:s', strtotime($request->ackdate));
            }
    
            if (!empty($request->job_completed_at)) {
                $task->job_completed_at = date('Y-m-d H:i:s', strtotime($request->job_completed_at));
            }

            $tempRmvL = Removal::where('id', $task->removal_id)->first();
            if ($tempRmvL) {
                $tempRental = \App\Models\Rental::find($tempRmvL->rental_id);
                if ($tempRental) {
                    $finalRmvl = $tempRmvL;
                    if (isset($finalRmvl->tsk->id) && isset($finalRmvl->tsk->removal_id)) {

                        $tempRental->perpetual_2 = $request->the_perpetual == 1 ? 1 : 0;
                        $tempRental->save();

                        if ($request->the_perpetual != 1) {//reverse final removal process
                            Task::where('id', $finalRmvl->tsk->id)->update(['job_type' => 1, 'perpetual_terminated' => false]);
                            Removal::where('id', $finalRmvl->tsk->removal_id)->update(['removal_type' => 0]);
                            
                            Task::whereHas('removal', function ($bldr) use ($finalRmvl) {
                                $bldr->where('rental_id', $finalRmvl->rental_id);
                            })

                            ->where(\DB::raw("DATE_FORMAT(task_date, '%Y-%m-%d %H:%i:%s')"), '>=', $finalRmvl->tsk->task_date)
                            ->where('id', '!=', $finalRmvl->tsk->id)
                            ->where('job_type', '!=', 0)
                            ->update(['status' => 0, 'perpetual_terminated' => false]);

                            Removal::where('rental_id', '!=', $finalRmvl->tsk->id)
                            ->whereHas('tsk', function ($bldr) use ($finalRmvl) {
                                $bldr->where(\DB::raw("DATE_FORMAT(task_date, '%Y-%m-%d %H:%i:%s')"), '>=', $finalRmvl->tsk->task_date)
                                ->where('id', '!=', $finalRmvl->tsk->id)
                                ->where('job_type', '!=', 0);
                            })
                            ->update(['status' => 0]);

                        } else {//make final removal
                            Task::where('id', $finalRmvl->tsk->id)->update(['job_type' => 2, 'perpetual_terminated' => true]);
                            Removal::where('id', $finalRmvl->tsk->removal_id)->update(['removal_type' => 2]);

                            Task::whereHas('removal', function ($bldr) use ($finalRmvl) {
                                $bldr->where('rental_id', $finalRmvl->rental_id);
                            })

                            ->where(\DB::raw("DATE_FORMAT(task_date, '%Y-%m-%d %H:%i:%s')"), '>=', $finalRmvl->tsk->task_date)
                            ->where('id', '!=', $finalRmvl->tsk->id)
                            ->where('job_type', '!=', 0)
                            ->update(['status' => 2, 'perpetual_terminated' => false]);

                            Removal::where('rental_id', '!=', $finalRmvl->tsk->id)
                            ->whereHas('tsk', function ($bldr) use ($finalRmvl) {
                                $bldr->where(\DB::raw("DATE_FORMAT(task_date, '%Y-%m-%d %H:%i:%s')"), '>=', $finalRmvl->tsk->task_date)
                                ->where('id', '!=', $finalRmvl->tsk->id)
                                ->where('job_type', '!=', 0);
                            })
                            ->update(['status' => 2]);

                        }

                    }
                }
            }
    
            if ($request->status == 5) {
                $tempRemoval = Removal::where('id', $task->removal_id)->first();
                if ($tempRemoval) {

                    $tempRental = \App\Models\Rental::find($tempRemoval->rental_id);
                    if ($tempRemoval->removal_type == 4) {
                        \App\Models\Rental::find($tempRemoval->rental_id)->update(['from_date' => Helper::minDate(date('Y-m-d H:i:s', strtotime($tempRental->real_delivery_date)), date('Y-m-d H:i:s')), 'delivery_date' => Helper::minDate(date('Y-m-d H:i:s', strtotime($tempRental->real_delivery_date)), date('Y-m-d H:i:s'))]);
                    }

                    if ($tempRental && $tempRental->perpetual_2 == 1) {
                        $tempRental->perpatual = 0;
                        $tempRental->save();
                    }
                }              
            }

            $task->save();
        
            \DB::commit();
            return redirect()->route('tasks.index')->with('success','Task updated successfully');
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('TASK UPDATATION ERROR : ' . $e->getMessage() . ' LINE : ' . $e->getLine());
            return redirect()->route('tasks.index')->with('error', Helper::$errorMessage);
        }
    }

    public function destroy($id)
    {
        \DB::beginTransaction();

        try {
            $task = Task::find(decrypt($id));
            InventoryItem::where('id', $task->skip_unit_id)->update([
                'available' => 1,
                'task_id' => null,
                'customer_id' => null,
                'customer_name' => null,
                'start_date' => null,
                'return_date' => now(),
                'driver_id' => null,
                'vehicle_id' => null,
                'last_driver_id' => $task->driver_id ?? null
            ]);
    
            Removal::find($task->removal_id)->update([
                'deleted_at' => now(),
                'restorable' => 0
            ]);
            $task->update([
                'deleted_at' => now(),
                'restorable' => 0
            ]);

            \DB::commit();
            return redirect()->route('tasks.index')->with('success','Task deleted successfully');
        } catch (\Exception $e) {
            \DB::rollBack();
            return redirect()->route('tasks.index')->with('error',Helper::$errorMessage);
        }
    }

    public function important($id)
    {
        \DB::beginTransaction();

        try {
            $task = Task::find(decrypt($id));
            $task->update([
                'importance' => 1
            ]);

            \DB::commit();
            return redirect()->route('tasks.index')->with('success','Task made important successfully');
        } catch (\Exception $e) {
            \DB::rollBack();
            return redirect()->route('tasks.index')->with('error',Helper::$errorMessage);
        }
    }

    public function unimportant($id)
    {
        \DB::beginTransaction();

        try {
            $task = Task::find(decrypt($id));
            $task->update([
                'importance' => 0
            ]);

            \DB::commit();
            return redirect()->route('tasks.index')->with('success','Task made unimportant successfully');
        } catch (\Exception $e) {
            \DB::rollBack();
            return redirect()->route('tasks.index')->with('error',Helper::$errorMessage);
        }
    }

    public function restore($id) {
        \DB::beginTransaction();

        try {

            $task = Task::onlyTrashed()->where('id', decrypt($id))->first();

            if ($task) {
                Removal::withTrashed()->where('id', $task->removal_id)->update([
                    'deleted_at' => null,
                    'restorable' => 1
                ]);
                Task::withTrashed()->where('id', $task->id)->update([
                    'deleted_at' => null,
                    'restorable' => 1
                ]);

                \DB::commit();
                return redirect()->route('tasks.index')->with('success','Task restored successfully');
            } else {
                \DB::rollBack();
                return redirect()->route('tasks.index')->with('error',Helper::$errorMessage);                
            }
        } catch (\Exception $e) {
            \DB::rollBack();
            return redirect()->route('tasks.index')->with('error',Helper::$errorMessage);
        }
    }

    public function get(Request $request)
    {
        $queryString = trim($request->searchQuery);
        $page = $request->input('page', 1);
        $limit = env('SELECT2_PAGE_LENGTH', 5);
    
        $query = Task::query();
    
        if (!empty($queryString)) {
            $query->where(function ($builder) use ($queryString) {
                return $builder->where('code', 'LIKE', "%{$queryString}%");
            });
        }
    
        $data = $query->paginate($limit, ['*'], 'page', $page);
    
        return response()->json([
            'items' => $data->map(function ($pro) {
                return [
                    'id' => $pro->id,
                    'text' => $pro->description . ' - ' . $pro->code
                ];
            }),
            'pagination' => [
                'more' => $data->hasMorePages()
            ]
        ]);
    }

    public function sortTasks(Request $request) {
        $tasks = $request->task_ids;

        foreach ($tasks as $order => $task) {
            Task::find($task)->update(['order' => $order]);
        }

        return response()->json(['status' => true]);
    }
}
