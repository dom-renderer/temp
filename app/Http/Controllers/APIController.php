<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\DisposalSiteRates;
use App\Models\InventoryItem;
use App\Models\DisposalSite;
use Illuminate\Http\Request;
use App\Models\DeviceToken;
use App\Models\Rental;
use App\Models\Location;
use App\Models\Removal;
use App\Helpers\Helper;
use App\Models\User;
use App\Models\Task;

class APIController extends Controller
{
    public $successStatus = 200;

    public function login(Request $request) {
        self::apiStatusLog("LOGIN API START", '/login', date('Y-m-d H:i:s'));
        $sTimestamp = microtime(true);

        $validator = \Validator::make($request->all(), [ 
            'username' => 'required',
            'password' => 'required'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error'=>$errorString], 401);           
        }

        if (Auth::attempt(['username' => $request->username, 'password' => $request->password])) { 

            $user = Auth::user();

            if ($user->roles[0]->id != Helper::$roles['driver']) {
                return response()->json(['error' => 'Login not allowed!'], 401);
            }

            if ($user->status != 1) {
                $eTimestamp = microtime(true);
                self::apiStatusLog("LOGIN API END: ERROR", '/login', date('Y-m-d H:i:s'), $eTimestamp - $sTimestamp);

                return response()->json(['error' => 'Your account is disabled by the admin!'], 401);
            } else {
                
                $success = [
                    'token' => $user->createToken('SkipApp')->accessToken,
                    'userId' => $user->id,
                    'userDetails' => [
                        'code' => $user->code,
                        'saluation' => $user->saluation,
                        'firstname' => $user->firstname,
                        'lastname' => $user->lastname,
                        'username' => $user->username,
                        'email' => $user->email,
                        'phone_number' => $user->phone_number,
                        'alt_phone_number' => $user->alt_phone_number,
                    ]
                ];

                $eTimestamp = microtime(true);
                self::apiStatusLog("LOGIN API END: SUCCESS", '/login', date('Y-m-d H:i:s'), $eTimestamp - $sTimestamp);

                return response()->json(['success' => $success], $this->successStatus); 
            }
        } else {

            $eTimestamp = microtime(true);
            self::apiStatusLog("LOGIN API END: ERROR", '/login', date('Y-m-d H:i:s'), $eTimestamp - $sTimestamp);
            return response()->json(['error' => 'Unauthorised'], 401);
        }
    }

    public function saveDeviceToken(Request $request) {
        self::apiStatusLog("SAVE DEVICE TOKEN API START", '/save-device-token', date('Y-m-d H:i:s'));
        $sTimestamp = microtime(true);

        $validator = \Validator::make($request->all(), [ 
            'user_id' => 'required|exists:users,id',
            'token' => 'required'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error'=>$errorString], 401);           
        }

        if (DeviceToken::where('token', $request->token)->exists()) {
            if (DeviceToken::where(function ($builder) {
                return $builder->whereNull('user_id')->orWhere('user_id', '');
            })->where('token', $request->token)->exists()) {
    
                DeviceToken::where(function ($builder) {
                    return $builder->whereNull('user_id')->orWhere('user_id', '');
                })->where('token', $request->token)->update([
                    'user_id' => $request->user_id
                ]);
    
            } else {
                DeviceToken::updateOrCreate([
                    'token' => $request->token
                ],[
                    'user_id' => $request->user_id,
                    'token' => $request->token
                ]);
            }
        } else {
            DeviceToken::updateOrCreate([
                'user_id' => $request->user_id,
                'token' => $request->token
            ]);
        }

        $eTimestamp = microtime(true);
        self::apiStatusLog("SAVE DEVICE TOKEN API END: SUCCESS", '/save-device-token', date('Y-m-d H:i:s'), $eTimestamp - $sTimestamp);

        return response()->json(['success' => 'Device Token Saved Successfully.']);
    }

    public function secondManList() {
        return response()->json(['success' => User::whereHas('roles', function ($builder) {
            return $builder->where('id', Helper::$roles['second-man']);
        })->select('id', 'code', 'name', 'username', 'saluation', 'email', 'firstname', 'lastname', 'phone_number', 'alt_phone_number')->get()]);
    }

    public function driverList(Request $request) {
        return response()->json(['success' => User::whereHas('roles', function ($builder) {
            return $builder->where('id', Helper::$roles['driver']);
        })->select('id', 'code', 'name', 'username', 'saluation', 'email', 'firstname', 'lastname', 'phone_number', 'alt_phone_number')->get()]);
    }

    public function driverWithSecondManList(Request $request) {
        return response()->json(['success' => User::whereHas('roles', function ($builder) {
            return $builder->whereIn('id', [Helper::$roles['driver'], Helper::$roles['second-man']]);
        })->select('id', 'code', 'name', 'username', 'saluation', 'email', 'firstname', 'lastname', 'phone_number', 'alt_phone_number')->get()]);
    }
    
    public function disposalSiteList(Request $request) {
        return response()->json(['success' => DisposalSite::select('id', 'code', 'name', 'description', 'tipping_fee_rate', 'tipping_fee_method')->get()]);
    }

    public function taskList(Request $request) {
        self::apiStatusLog("TASK LIST API START", '/task-list', date('Y-m-d H:i:s'));
        $sTimestamp = microtime(true);

        $validator = \Validator::make($request->all(), [ 
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error'=>$errorString], 401);           
        }

        if (auth()->user()->roles[0]->id != Helper::$roles['driver']) {
            return response()->json(['error' => 'Data Access allowed!'], 401);
        }

        $tasks = [];
        $search = $request->search;

        foreach (Task::with(['customer', 'driver', 'second', 'location' => function ($builder) {
            return $builder->withTrashed();
        }])
        ->whereHas('customer', function ($builder) use ($search) {
            $builder->where('status', 1)->where('on_hold', 0)
            ->when(is_string($search) && !empty($search), function ($innerBuilder) use ($search) {
                $innerBuilder->where('name', 'LIKE', "%{$search}%");
            });
        })
        ->where('driver_id', $request->user_id)
        ->where('is_approved', 1)
        ->whereIn('status', [1, 4])
        ->orderBy('order', 'ASC')
        ->get() as $task) {

            $currentSkip = null;
            $replacableSkip = null;

            if (isset($task->assignedskip)) {
                $currentSkip = [
                    'id' => $task->assignedskip->id,
                    'code' => $task->assignedskip->code,
                    'qr_path' => is_file(public_path("storage/skip-qr/{$task->assignedskip->qr_path}")) ? url("storage/skip-qr/{$task->assignedskip->qr_path}") : '',
                    'qr_code' => $task->assignedskip->qr_string,
                    'description' => $task->assignedskip->description,
                    'status' => $task->assignedskip->available,
                    'status_text' => (
                        $task->assignedskip->available == 0 ? 'Reserved' : (
                            $task->assignedskip->available == 1 ? 'Off Hire' : (
                                $task->assignedskip->available == 2 ? 'On Hire' : ''
                            )
                        )
                    ),
                ];
            }

            if (isset($task->skipusedintask)) {
                $replacableSkip = [
                    'id' => $task->skipusedintask->id,
                    'code' => $task->skipusedintask->code,
                    'qr_path' => is_file(public_path("storage/skip-qr/{$task->skipusedintask->qr_path}")) ? url("storage/skip-qr/{$task->skipusedintask->qr_path}") : '',
                    'qr_code' => $task->skipusedintask->qr_string,
                    'description' => $task->skipusedintask->description,
                    'status' => $task->skipusedintask->available,
                    'status_text' => (
                        $task->skipusedintask->available == 0 ? 'Reserved' : (
                            $task->skipusedintask->available == 1 ? 'Off Hire' : (
                                $task->skipusedintask->available == 2 ? 'On Hire' : ''
                            )
                        )
                    ),
                ];
            }

            $tasks[] = [
                'id' => $task->id,
                'code'=> !empty($task->legacy_code) ? $task->legacy_code : $task->code,
                'code_2'=> $task->code,
                'order'=> $task->order,
                'importance'=> $task->importance,
                'ticket_number'=> $task->ticket_number,
                'customer_id' => $task->customer_id,
                'customer_name' => ($task->customer->name ?? ''),
                'customer_address' => $task->address,
                'task_location' => [
                    'id' => $task->location->id ?? '',
                    'code' => $task->location->code ?? '',
                    'address' => $task->location->address ?? '',
                    'contact' => $task->location->contact ?? '',
                    'work_telephone' => $task->location->work_telephone ?? '',
                    'fax' => $task->location->fax ?? '',
                    'email' => $task->location->email ?? '',
                    'latitude' => $task->location->latitude ?? '',
                    'longitude' => $task->location->longitude ?? '',
                    'directions' => $task->location->description ?? '',
                ],
                'current_skip' => $currentSkip,
                'to_be_replaced_skip' => $replacableSkip,
                'description' => $task->description,
                'short_description' => $task->description,
                'job_type' => $task->service_type == 1 ? 'SERVICE' : ($task->job_type == 0 ? 'RENTAL' : ($task->job_type == 2 ? 'FINAL_REMOVAL' : 'REMOVAL')),
                'service_type' => $task->service_type == 0 ? 'RENTAL' : 'SERVICE',
                'second_man' => $task->secondman_id,
                'task_date' => date('Y-m-d', strtotime($task->task_date)),
                'task_time' => date('H:i', strtotime($task->task_date)),
                'job_starting_time' => !empty($task->job_started_at) ? date('Y-m-d H:i', strtotime($task->job_started_at)) : null,
                'job_completion_time' => !empty($task->job_completed_at) ? date('Y-m-d H:i', strtotime($task->job_completed_at)) : null,
                'singature_person_name' => $task->acknowledge_by,
                'singature_time' => !empty($task->acknowledged_at) ? date('Y-m-d H:i', strtotime($task->acknowledged_at)) : null,
                'signature' => !empty($task->signature) ? url("storage/signatures/{$task->signature}") : null,
                'signature_status' => $task->signature_status,
                'signature_status_text' => ($task->signature_status == 1 ? 'SIGNATURED' : (
                    $task->signature_status == 2 ? 'HOLD' : (
                        $task->signature_status == 3 ? 'SIGNATURED' : ''
                    )
                )),
                'load_description' => $task->load,
                'status' => ($task->status == 0 ? 'Ordered' : (
                    $task->status == 1 ? 'Pending' : (
                        $task->status == 2 ? 'Cancelled' : (
                            $task->status == 3 ? 'On Hold' : (
                                $task->status == 4 ? 'In Progress' : (
                                    $task->status == 5 ? 'Completed' : ''
                                )
                            )
                        )
                    )
                )),
                'disposal_site_id' => $task->disposal_site_id,
                'disposal_time' => !empty($task->disposal_at) ? date('Y-m-d H:i', strtotime($task->disposal_at)) : null,

            ];
        }

        $eTimestamp = microtime(true);
        self::apiStatusLog("TASK LIST API END: SUCCESS", '/task-list', date('Y-m-d H:i:s'), $eTimestamp - $sTimestamp);

        return response()->json(['success' => $tasks]);
    }

    public function getTask(Request $request) {
        self::apiStatusLog("GET TASK API START", '/get-task', date('Y-m-d H:i:s'));
        $sTimestamp = microtime(true);

        $validator = \Validator::make($request->all(), [ 
            'task_id' => 'required|exists:tasks,id'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error'=>$errorString], 401);           
        }

        $task = Task::with(['customer', 'driver', 'second', 'location' => function ($builder) {
            return $builder->withTrashed();
        }])->where('id', $request->task_id)
        ->where('is_approved', 1)
        ->whereIn('status', [1, 4])
        ->orderBy('order', 'ASC')
        ->first();

        if (!$task) {
            return response()->json(['error' => 'Task Not Found.']);
        }

        $currentSkip = null;
        $replacableSkip = null;

        if (isset($task->assignedskip)) {
            $currentSkip = [
                'id' => $task->assignedskip->id,
                'code' => $task->assignedskip->code,
                'qr_path' => is_file(public_path("storage/skip-qr/{$task->assignedskip->qr_path}")) ? url("storage/skip-qr/{$task->assignedskip->qr_path}") : '',
                'qr_code' => $task->assignedskip->qr_string,
                'description' => $task->assignedskip->description,
                'status' => $task->assignedskip->available,
                'status_text' => (
                    $task->assignedskip->available == 0 ? 'Reserved' : (
                        $task->assignedskip->available == 1 ? 'Off Hire' : (
                            $task->assignedskip->available == 2 ? 'On Hire' : ''
                        )
                    )
                ),
            ];
        }

        if (isset($task->skipusedintask)) {
            $replacableSkip = [
                'id' => $task->skipusedintask->id,
                'code' => $task->skipusedintask->code,
                'qr_path' => is_file(public_path("storage/skip-qr/{$task->skipusedintask->qr_path}")) ? url("storage/skip-qr/{$task->skipusedintask->qr_path}") : '',
                'qr_code' => $task->skipusedintask->qr_string,
                'description' => $task->skipusedintask->description,
                'status' => $task->skipusedintask->available,
                'status_text' => (
                    $task->skipusedintask->available == 0 ? 'Reserved' : (
                        $task->skipusedintask->available == 1 ? 'Off Hire' : (
                            $task->skipusedintask->available == 2 ? 'On Hire' : ''
                        )
                    )
                ),
            ];
        }

        $data = [
            'id' => $task->id,
            'code'=> $task->legacy_code,
            'code_2'=> $task->code,
            'order'=> $task->order,
            'importance'=> $task->importance,
            'ticket_number'=> $task->ticket_number,
            'customer_id' => $task->customer_id,
            'customer_name' => ($task->customer->name ?? ''),
            'customer_address' => $task->address,
            'task_location' => [
                'id' => $task->location->id ?? '',
                'code' => $task->location->code ?? '',
                'address' => $task->location->address ?? '',
                'contact' => $task->location->contact ?? '',
                'work_telephone' => $task->location->work_telephone ?? '',
                'fax' => $task->location->fax ?? '',
                'email' => $task->location->email ?? '',
                'latitude' => $task->location->latitude ?? '',
                'longitude' => $task->location->longitude ?? '',
                'directions' => $task->location->description ?? '',
            ],
            'current_skip' => $currentSkip,
            'to_be_replaced_skip' => $replacableSkip,
            'description' => $task->description,
            'short_description' => $task->description,
            'job_type' => $task->service_type == 1 ? 'SERVICE' : ($task->job_type == 0 ? 'RENTAL' : ($task->job_type == 2 ? 'FINAL_REMOVAL' : 'REMOVAL')),
            'service_type' => $task->service_type == 0 ? 'RENTAL' : 'SERVICE',
            'second_man' => $task->secondman_id,
            'task_date' => date('Y-m-d', strtotime($task->task_date)),
            'task_time' => date('H:i', strtotime($task->task_date)),
            'job_starting_time' => !empty($task->job_started_at) ? date('Y-m-d H:i', strtotime($task->job_started_at)) : null,
            'job_completion_time' => !empty($task->job_completed_at) ? date('Y-m-d H:i', strtotime($task->job_completed_at)) : null,
            'singature_person_name' => $task->acknowledge_by,
            'singature_time' => !empty($task->acknowledged_at) ? date('Y-m-d H:i', strtotime($task->acknowledged_at)) : null,
            'signature' => !empty($task->signature) ? url("storage/signatures/{$task->signature}") : null,
            'signature_status_text' => ($task->signature_status == 1 ? 'SIGNATURED' : (
                $task->signature_status == 2 ? 'HOLD' : (
                    $task->signature_status == 3 ? 'SIGNATURED' : ''
                )
            )),
            'load_description' => $task->load,
            'status' => ($task->status == 0 ? 'Ordered' : (
                $task->status == 1 ? 'Pending' : (
                    $task->status == 2 ? 'Cancelled' : (
                        $task->status == 3 ? 'On Hold' : (
                            $task->status == 4 ? 'In Progress' : (
                                $task->status == 5 ? 'Completed' : ''
                            )
                        )
                    )
                )
            )),
            'disposal_site_id' => $task->disposal_site_id,
            'disposal_time' => !empty($task->disposal_at) ? date('Y-m-d H:i', strtotime($task->disposal_at)) : null
        ];

        $eTimestamp = microtime(true);
        self::apiStatusLog("GET TASK API END: SUCCESS", '/get-task', date('Y-m-d H:i:s'), $eTimestamp - $sTimestamp);

        return response()->json(['success' => $data]);
    }

    public function assignSkip(Request $request) {
        self::apiStatusLog("ASSIGN SKIP API START", '/assign-skip', date('Y-m-d H:i:s'));
        $sTimestamp = microtime(true);

        $validator = \Validator::make($request->all(), [ 
            'qr_code' => 'required|exists:inventory_items,qr_string',
            'task_id' => 'required|exists:tasks,id'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error'=>$errorString], 401);           
        }

        $task = Task::where('id', $request->task_id)
        ->where('status', 1)
        ->first();

        $newSkip = InventoryItem::where('qr_string', $request->qr_code)
        ->where('available', 1)
        ->where(function ($builder) {
            return $builder->whereNull('task_id')
            ->orWhere('task_id', '');
        })
        ->first();

        if (!$newSkip) {
            return response()->json(['error' => 'Skip unit is not available.']);
        }

        if ($task) {
            \DB::beginTransaction();

            try {
                if ($task->skip_unit_id > 0) {
                    InventoryItem::where('id', $task->skip_unit_id)->update([
                        'available' => 1,
                        'task_id' => null,
                        'customer_id' => null,
                        'customer_name' => null,
                        'start_date' => null,
                        'return_date' => now(),
                        'driver_id' => null,
                        'last_driver_id' => $task->driver_id,
                        'vehicle_id' => null
                    ]);
                }

                Task::find($request->task_id)->update([
                    'skip_unit_id' => $newSkip->id
                ]);

                InventoryItem::where('id', $task->skip_unit_id)->update([
                    'available' => 2,
                    'task_id' => $task->id,
                    'customer_id' => $request->customer,
                    'customer_name' => ($customerDetails->name ?? ''),
                    'start_date' => date('Y-m-d H:i:s', strtotime($request->date)),
                    'return_date' => date('Y-m-d H:i:s', strtotime($request->dispdate)),
                    'driver_id' => $request->driver,
                    'vehicle_id' => $request->vehicle,
                ]);

                \DB::commit();
                return response()->json(['success' => 'Skip assigned successfully.']);
            } catch (\Exception $e) {
                \DB::rollBack();
                return response()->json(['error' => Helper::$errorMessage]);
            }
        }

        $eTimestamp = microtime(true);
        self::apiStatusLog("ASSIGN SKIP API END: SUCCESS", '/assign-skip', date('Y-m-d H:i:s'), $eTimestamp - $sTimestamp);

        return response()->json(['error' => 'Task is not in pending stage.']);
    }

    public function pendingToInProgress (Request $request) {
        self::apiStatusLog("PENDING TO INPROGRESS API START", '/pending-to-inprogress', date('Y-m-d H:i:s'));
        $sTimestamp = microtime(true);

        $validator = \Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,id'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error'=>$errorString], 401);           
        }

        $task = Task::where('id', $request->task_id)
        ->when($request->allow_any_status != '1', function ($builder) {
            return $builder->where('status', 1);
        })
        ->first();     

        if ($task) {
            Task::find($request->task_id)->update([
                'status' => 4,
                'job_started_at' => now()
            ]);
            Removal::where('id', $task->removal_id)->update([
                'status' => 4                
            ]);

            return response()->json(['success' => 'Task updated to in-progress.']);
        }

        $eTimestamp = microtime(true);
        self::apiStatusLog("PENDING TO INPROGRESS API END: SUCCESS", '/pending-to-inprogress', date('Y-m-d H:i:s'), $eTimestamp - $sTimestamp);

        return response()->json(['error' => 'Task is not in pending stage.']);
    }
    
    public function addSecondMan(Request $request) {
        self::apiStatusLog("SECOND MAN ADD API START", '/add-second-man', date('Y-m-d H:i:s'));
        $sTimestamp = microtime(true);

        $validator = \Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,id',
            'second_man_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error'=>$errorString], 401);           
        }

        $isUserDriverOrSecondMan = User::where('id', $request->second_man_id)->whereHas('roles', function ($builder) {
            return $builder->whereIn('id', [Helper::$roles['driver'], Helper::$roles['second-man']]);
        })->doesntExist();

        if ($isUserDriverOrSecondMan) {
            return response()->json(['error' => 'The user must be either second-man or driver.']);
        }

        Task::find($request->task_id)->update([
            'secondman_id' => $request->second_man_id
        ]);

        $eTimestamp = microtime(true);
        self::apiStatusLog("SECOND MAN ADD API END: SUCCESS", '/add-second-man', date('Y-m-d H:i:s'), $eTimestamp - $sTimestamp);

        return response()->json(['success' => 'Task\'s second man updated successfully.']);
    }

    public function addLoadDescription(Request $request) {
        self::apiStatusLog("ADD LOAD DESCRIPTION API START", '/add-load-description', date('Y-m-d H:i:s'));
        $sTimestamp = microtime(true);

        $validator = \Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,id'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error'=>$errorString], 401);           
        }

        Task::find($request->task_id)->update([
            'load' => $request->load
        ]);

        $eTimestamp = microtime(true);
        self::apiStatusLog("ADD LOAD DESCRIPTION API END: SUCCESS", '/add-load-description', date('Y-m-d H:i:s'), $eTimestamp - $sTimestamp);

        return response()->json(['success' => 'Task\'s load description updated successfully.']);
    }

    public function changeLatLong(Request $request) {
        self::apiStatusLog("CHANGE LAT LONG API START", '/change-lat-long', date('Y-m-d H:i:s'));
        $sTimestamp = microtime(true);

        $validator = \Validator::make($request->all(), [
            'location_id' => 'required|exists:locations,id',
            'latitude' => 'required',
            'longitude' => 'required'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error'=>$errorString], 401);           
        }
        
        Location::where('id', $request->location_id)->update([
            'latitude' => $request->latitude,
            'longitude' => $request->longitude
        ]);

        $eTimestamp = microtime(true);
        self::apiStatusLog("CHANGE LAT LONG API END: SUCCESS", '/change-lat-long', date('Y-m-d H:i:s'), $eTimestamp - $sTimestamp);

        return response()->json(['success' => 'Location updated successfully.']);
    }

    public function changeDisposalSite(Request $request) {
        self::apiStatusLog("CHANGE DISPOSAL SITES API START", '/change-disposal-site', date('Y-m-d H:i:s'));
        $sTimestamp = microtime(true);

        $validator = \Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,id',
            'disposal_site_id' => 'required|exists:disposal_sites,id'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error'=>$errorString], 401);           
        }

        Task::find($request->task_id)->update([
            'disposal_site_id' => $request->disposal_site_id
        ]);

        $eTimestamp = microtime(true);
        self::apiStatusLog("CHANGE DISPOSAL SITES API END: SUCCESS", '/change-disposal-site', date('Y-m-d H:i:s'), $eTimestamp - $sTimestamp);

        return response()->json(['success' => 'Task\'s disposal site updated successfully.']);
    }

    public function completeMultipleTasks(Request $request) {
        self::apiStatusLog("COMPLETE MULTIPLE TASKS API START", '/complete-multiple-tasks', date('Y-m-d H:i:s'));
        $sTimestamp = microtime(true);

        $validator = \Validator::make($request->all(), [
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:tasks,id',
            'disposal_site_ids' => 'required|array',
            'disposal_site_ids.*' => 'exists:disposal_sites,id'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error'=>$errorString], 401);           
        }

        $tasks = $request->task_ids;

        \DB::beginTransaction();

        try {

            foreach ($tasks as $key => $task) {
                $task = Task::where('id', $task)->first();

                if ($task) {
                    Task::find($task->id)->update([
                        'status' => 5,
                        'disposal_site_id' => $request->disposal_site_ids[$key] ?? 1,
                        'job_completed_at' => now()
                    ]);
                    Removal::where('id', $task->removal_id)->update([
                        'status' => 5
                    ]);

                    $tempRemoval = Removal::where('id', $task->removal_id)->first();
                    if ($tempRemoval) {

                        $tempRental = Rental::find($tempRemoval->rental_id);
                        if ($tempRemoval->removal_type == 4) {
                            Rental::find($tempRemoval->rental_id)->update(['from_date' => Helper::minDate(date('Y-m-d H:i:s', strtotime($tempRental->real_delivery_date)), date('Y-m-d H:i:s')), 'delivery_date' => Helper::minDate(date('Y-m-d H:i:s', strtotime($tempRental->real_delivery_date)), date('Y-m-d H:i:s'))]);
                        }

                        if ($tempRental && $tempRental->perpetual_2 == 1) {
                            $tempRental->perpatual = 0;
                            $tempRental->save();
                        }
                    }
    
                    $skip = InventoryItem::where('id', $task->skip_unit_id)->first();
                    if ($skip) {
                        InventoryItem::where('id', $task->skip_unit_id)->update([
                            'available' => 1,
                            'task_id' => null,
                            'customer_id' => null,
                            'customer_name' => null,
                            'start_date' => null,
                            'return_date' => now(),
                            'driver_id' => null,
                            'vehicle_id' => null,
                            'last_driver_id' => $task->driver_id
                        ]);
                    }
                }
            }

            $eTimestamp = microtime(true);
            self::apiStatusLog("COMPLETE MULTIPLE TASKS API END: SUCCESS", '/complete-multiple-tasks', date('Y-m-d H:i:s'), $eTimestamp - $sTimestamp);

            \DB::commit();
            return response()->json(['success' => 'Task completed successfully.']);
        } catch (\Exception $e) {
            $eTimestamp = microtime(true);
            self::apiStatusLog("COMPLETE MULTIPLE TASKS API END: ERROR", '/complete-multiple-tasks', date('Y-m-d H:i:s'), $eTimestamp - $sTimestamp);

            \DB::rollBack();
            return response()->json(['error' => Helper::$errorMessage]);
        }
    }

    public function signatureStatus(Request $request) {
        self::apiStatusLog("SIGNATURE STATUS API START", '/signature-status', date('Y-m-d H:i:s'));
        $sTimestamp = microtime(true);

        $validator = \Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,id',
            'status' => 'required|in:1,2,3,4'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error'=>$errorString], 401);
        }

        $task = Task::with('customer')->where('id', $request->task_id)
        ->whereIn('status', [1, 4])
        ->first();     

        if (!$task) {
            return response()->json(['error' => 'Task is not in pending or in-progress.']);
        }

        \DB::beginTransaction();

        try {
            if ($request->status == 1) {
                if (isset($task->customer->id)) {
                    $getLastTicketNumber = User::withTrashed()->where('id', $task->customer->id ?? '')->first()->current_ticket_index ?? 0;
                    if ($getLastTicketNumber > 0) {
                        $getLastTicketNumber += 1;
                        $ticketReferenceNumber = sprintf('%09d', $getLastTicketNumber);
                    } else {
                        $ticketReferenceNumber = sprintf('%09d', 1);
                        $getLastTicketNumber = 1;
                    }

                    $uElo = User::find($task->customer->id);
                    $uElo->current_ticket_index = $getLastTicketNumber;
                    $uElo->save();

                    $ticketReferenceNumber = $task->customer->id . $ticketReferenceNumber;
                }

                Task::find($request->task_id)->update([
                    'signature_status' => 1,
                    'ticket_number' => $ticketReferenceNumber
                ]);
            } else if ($request->status == 2) {
                Task::find($request->task_id)->update(['signature_status' => 2]);
            } else if ($request->status == 3) {
                Task::find($request->task_id)->update(['signature_status' => 0, 'status' => 1]);
                Removal::where('id', $request->removal_id)->update(['status' => 1]);
            } else if ($request->status == 4) {
                if (!$request->hasFile('signature')) {
                    return response()->json(['error' => 'Please upload a signature.']);
                }
    
                if (!file_exists(storage_path('app/public/signatures'))) {
                    mkdir(storage_path('app/public/signatures'), 0777, true);
                }
    
                $signaturePath = 'SIGN-' . date('YmdHis') . uniqid() . '.' . $request->file('signature')->getClientOriginalExtension();
                $request->file('signature')->move(storage_path('app/public/signatures'), $signaturePath);
    
                $ackTime = now();

                if (isset($task->customer->id)) {
                    $getLastTicketNumber = User::withTrashed()->where('id', $task->customer->id ?? '')->first()->current_ticket_index ?? 0;
                    if ($getLastTicketNumber > 0) {
                        $getLastTicketNumber += 1;
                        $ticketReferenceNumber = sprintf('%09d', $getLastTicketNumber);
                    } else {
                        $ticketReferenceNumber = sprintf('%09d', 1);
                        $getLastTicketNumber = 1;
                    }

                    $uElo = User::find($task->customer->id);
                    $uElo->current_ticket_index = $getLastTicketNumber;
                    $uElo->save();

                    $ticketReferenceNumber = $task->customer->id . $ticketReferenceNumber;
                }

                Task::find($request->task_id)->update([
                    'signature_status' => 3,
                    'signature' => $signaturePath,
                    'acknowledge_by' => $request->signature_person ?? '',
                    'acknowledged_at' => $ackTime,
                    'ticket_number' => $ticketReferenceNumber
                ]);

                if (isset($task->customer->email) && filter_var($task->customer->email, FILTER_VALIDATE_EMAIL)) {
                    $thisEmail = $task->customer->email;
                    \App\Jobs\SendSignatureMailViaJob::dispatch($task, $ackTime, $signaturePath, $thisEmail, 'Skip Services');
                }

                if (isset($task->location->email) && filter_var($task->location->email, FILTER_VALIDATE_EMAIL)) {
                    $thisEmail = $task->location->email;
                    if (!(isset($task->customer->email) && $task->customer->email == $thisEmail)) {
                        \App\Jobs\SendSignatureMailViaJob::dispatch($task, $ackTime, $signaturePath, $thisEmail, 'Skip Services');
                    }
                }

                if (isset($task->email) && filter_var($task->email, FILTER_VALIDATE_EMAIL)) {
                    $thisEmail = $task->email;
                    if (!((isset($task->location->email) && $task->location->email == $thisEmail) || (isset($task->customer->email) && $task->customer->email == $thisEmail))) {
                        \App\Jobs\SendSignatureMailViaJob::dispatch($task, $ackTime, $signaturePath, $thisEmail, 'Skip Services');
                    }
                }
            }

            $eTimestamp = microtime(true);
            self::apiStatusLog("SIGNATURE STATUS API END: SUCCESS", '/signature-status', date('Y-m-d H:i:s'), $eTimestamp - $sTimestamp);

            \DB::commit();
            return response()->json(['success' => 'Signature status updated successfully.']);
        } catch (\Exception $e) {
            $eTimestamp = microtime(true);
            self::apiStatusLog("SIGNATURE STATUS API END: ERROR", '/signature-status', date('Y-m-d H:i:s'), $eTimestamp - $sTimestamp);

            \DB::rollBack();
            \Log::error($e->getMessage() . ' ON LINE ' . $e->getLine());
            return response()->json(['error' => Helper::$errorMessage]);
        }

    }

    public function sendMailJob(Request $request) {
        self::apiStatusLog("SEND MAIL JOB API START", '/send-mail-job', date('Y-m-d H:i:s'));
        $sTimestamp = microtime(true);

        $validator = \Validator::make($request->all(), [ 
            'task_id' => 'required|exists:tasks,id'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error'=>$errorString], 401);           
        }

        try {
            $task = Task::where('id', $request->task_id)->first();

            $admins = User::whereHas('roles', function ($builder) {
                return $builder->where('id', 1);
            })->select('email')->whereNotNull('email')->where('email', '!=', '')->pluck('email')->toArray();

            foreach ($admins as $admin) {
                if (filter_var($admin, FILTER_VALIDATE_EMAIL)) {
                    \App\Jobs\SendSignatureMailViaJob::dispatch($task, now(), '', $admin, 'Skip Services');
                }
            }

            $eTimestamp = microtime(true);
            self::apiStatusLog("SEND MAIL JOB API END: SUCCESS", '/send-mail-job', date('Y-m-d H:i:s'), $eTimestamp - $sTimestamp);

            return response()->json(['success' => 'Mail sent successfully.']);
        } catch (\Exception $e) {
            $eTimestamp = microtime(true);
            self::apiStatusLog("SEND MAIL JOB API END: ERROR", '/send-mail-job', date('Y-m-d H:i:s'), $eTimestamp - $sTimestamp);

            \Log::error($e->getMessage() . ' ON LINE ' . $e->getLine());
            return response()->json(['error' => Helper::$errorMessage]);
        }
    }

    public function sync(Request $request) {
        self::apiStatusLog("SYNC API START", '/sync', date('Y-m-d H:i:s'));
        $sTimestamp = microtime(true);

        \DB::beginTransaction();
        
        $signaturesToUnlink = $dataToReturn = [];

        try {

            /** START **/

            $tasks = $request->data;

            if (is_array($tasks) && count($tasks) > 0) {

                foreach ($tasks as $key => &$object) {
                    $task = Task::with(['location', 'customer'])->where('id', $object['task_id'])->first();
                    $object = $object['task_data'];

                    if ($task instanceof Task && count($object) > 0) {
                        foreach ($object as $objectKey => $objectValue) {
                            if ($objectKey == 'task_skip_unit_id') {

                                $newSkip = InventoryItem::where('qr_string', $objectValue)
                                ->where('available', 1)
                                ->where(function ($builder) {
                                    return $builder->whereNull('task_id')
                                    ->orWhere('task_id', '');
                                })
                                ->first();

                                if ($newSkip) {
                                    if ($task->skip_unit_id > 0) {
                                        InventoryItem::where('id', $task->skip_unit_id)->update([
                                            'available' => 1,
                                            'task_id' => null,
                                            'customer_id' => null,
                                            'customer_name' => null,
                                            'start_date' => null,
                                            'return_date' => now(),
                                            'driver_id' => null,
                                            'vehicle_id' => null,
                                            'last_driver_id' => $task->driver_id
                                        ]);
                                    }
                    
                                    Task::find($task->id)->update([
                                        'skip_unit_id' => $newSkip->id
                                    ]);
                    
                                    InventoryItem::where('id', $task->skip_unit_id)->update([
                                        'available' => 2,
                                        'task_id' => $task->id,
                                        'customer_id' => $task->customer->id ?? '',
                                        'customer_name' => ($task->customer->name ?? ''),
                                        'start_date' => now()
                                    ]);

                                    $dataToReturn[$task->id][] = $objectKey;
                                }
                            } else if ($objectKey == 'status') {
                                if ($objectValue == 4) {
                                    $tempTask = Task::where('id', $task->id)->first();
                                    if ($tempTask) {
                                        Task::find($tempTask->id)->update([
                                            'status' => 4,
                                            'job_started_at' => now()
                                        ]);
                                        Removal::where('id', $tempTask->removal_id)->update([
                                            'status' => 4                
                                        ]);
                                        $dataToReturn[$tempTask->id][] = $objectKey;
                                    }
                                } else if ($objectValue == 5) {
                                    Task::find($task->id)->update([
                                        'status' => 5,
                                        'disposal_site_id' => $request->data[$key]['task_data']['disposal_site_id'] ?? null,
                                        'job_completed_at' => now()
                                    ]);
                                    Removal::where('id', $task->removal_id)->update([
                                        'status' => 5
                                    ]);
                                    $tempRemoval = Removal::where('id', $task->removal_id)->first();
                                    if ($tempRemoval) {

                                        $tempRental = Rental::find($tempRemoval->rental_id);
                                        if ($tempRemoval->removal_type == 4) {
                                            Rental::find($tempRemoval->rental_id)->update(['from_date' => Helper::minDate(date('Y-m-d H:i:s', strtotime($tempRental->real_delivery_date)), date('Y-m-d H:i:s')), 'delivery_date' => Helper::minDate(date('Y-m-d H:i:s', strtotime($tempRental->real_delivery_date)), date('Y-m-d H:i:s'))]);
                                        }

                                        if ($tempRental && $tempRental->perpetual_2 == 1) {
                                            $tempRental->perpatual = 0;
                                            $tempRental->save();
                                        }
                                    }
                    
                                    $skip = InventoryItem::where('id', $task->skip_unit_id)->first();
                                    if ($skip) {
                                        InventoryItem::where('id', $task->skip_unit_id)->update([
                                            'available' => 1,
                                            'task_id' => null,
                                            'customer_id' => null,
                                            'customer_name' => null,
                                            'start_date' => null,
                                            'return_date' => now(),
                                            'driver_id' => null,
                                            'vehicle_id' => null,
                                            'last_driver_id' => $task->driver_id
                                        ]);
                                    }

                                    $dataToReturn[$task->id][] = $objectKey;
                                }
                            } else if ($objectKey == 'secondman_id') {
                                $isUserDriverOrSecondMan = User::where('id', $request->data[$key]['task_data']['secondman_id'] ?? null)->whereHas('roles', function ($builder) {
                                    return $builder->whereIn('id', [Helper::$roles['driver'], Helper::$roles['second-man']]);
                                })->exists();

                                if ($isUserDriverOrSecondMan) {
                                    Task::find($task->id)->update([
                                        'secondman_id' => $request->data[$key]['task_data']['secondman_id'] ?? null
                                    ]);

                                    $dataToReturn[$task->id][] = $objectKey;
                                }
                            } else if ($objectKey == 'load') {
                                Task::find($task->id)->update([
                                    'load' => $request->data[$key]['task_data']['load'] ?? ''
                                ]);

                                $dataToReturn[$task->id][] = $objectKey;
                            } else if ($objectKey == 'latitude') {

                                if (isset($task->location->id)) {
                                    if (!empty($request->data[$key]['task_data']['latitude'])) {
                                        Location::where('id', $task->location->id)->update([
                                            'latitude' => $request->data[$key]['task_data']['latitude']
                                        ]);

                                        $dataToReturn[$task->id][] = $objectKey;
                                    }
                                }
                            } else if ($objectKey == 'longitude') {

                                if (isset($task->location->id)) {
                                    if (!empty($request->data[$key]['task_data']['longitude'])) {
                                        Location::where('id', $task->location->id)->update([
                                            'longitude' => $request->data[$key]['task_data']['longitude']
                                        ]);

                                        $dataToReturn[$task->id][] = $objectKey;
                                    }
                                }
                            } else if ($objectKey == 'disposal_site_id') {
                                Task::find($task->id)->update([
                                    'disposal_site_id' => $request->data[$key]['task_data']['disposal_site_id'] ?? null
                                ]);
                                
                                $dataToReturn[$task->id][] = $objectKey;
                            } else if ($objectKey == 'signature_status') {

                                if ($objectValue == 1) {
                                    $ticketReferenceNumber = '';
                                    if (isset($task->customer->id)) {
                                        $getLastTicketNumber = User::withTrashed()->where('id', $task->customer->id ?? '')->first()->current_ticket_index ?? 0;
                                        if ($getLastTicketNumber > 0) {
                                            $getLastTicketNumber += 1;
                                            $ticketReferenceNumber = sprintf('%09d', $getLastTicketNumber);
                                        } else {
                                            $ticketReferenceNumber = sprintf('%09d', 1);
                                            $getLastTicketNumber = 1;
                                        }

                                        $uElo = User::find($task->customer->id);
                                        $uElo->current_ticket_index = $getLastTicketNumber;
                                        $uElo->save();

                                        $ticketReferenceNumber = $task->customer->id . $ticketReferenceNumber;
                                    }

                                    Task::find($task->id)->update([
                                        'signature_status' => 1,
                                        'ticket_number' => $ticketReferenceNumber
                                    ]);
                                    $dataToReturn[$task->id][] = $objectKey;
                                } else if ($objectValue == 2) {
                                    Task::find($task->id)->update(['signature_status' => 2]);
                                    $dataToReturn[$task->id][] = $objectKey;
                                } else if ($objectValue == 3) {
                                    Task::find($task->id)->update(['signature_status' => 0, 'status' => 1]);
                                    Removal::where('id', $task->removal->id ?? null)->update(['status' => 1]);
                                    $dataToReturn[$task->id][] = $objectKey;
                                } else if ($objectValue == 4) {
                                    if (isset($request->data[$key]['task_data']['signature']) && Helper::isBase64($request->data[$key]['task_data']['signature'])) {
                                        if (!file_exists(storage_path('app/public/signatures'))) {
                                            mkdir(storage_path('app/public/signatures'), 0777, true);
                                        }
                            
                                        $signaturePath = Helper::downloadBase64File($request->data[$key]['task_data']['signature'], ('SIGN-' . date('YmdHis') . uniqid()), storage_path('app/public/signatures'));                            
                                        $signaturesToUnlink[] = $signaturePath;
                                        $ackTime = now();
                        
                                        $ticketReferenceNumber = '';
                                        if (isset($task->customer->id)) {
                                            $getLastTicketNumber = User::withTrashed()->where('id', $task->customer->id ?? '')->first()->current_ticket_index ?? 0;
                                            if ($getLastTicketNumber > 0) {
                                                $getLastTicketNumber += 1;
                                                $ticketReferenceNumber = sprintf('%09d', $getLastTicketNumber);
                                            } else {
                                                $ticketReferenceNumber = sprintf('%09d', 1);
                                                $getLastTicketNumber = 1;
                                            }
    
                                            $uElo = User::find($task->customer->id);
                                            $uElo->current_ticket_index = $getLastTicketNumber;
                                            $uElo->save();

                                            $ticketReferenceNumber = $task->customer->id . $ticketReferenceNumber;
                                        }

                                        Task::find($task->id)->update([
                                            'signature_status' => 3,
                                            'signature' => $signaturePath,
                                            'acknowledge_by' => $request->data[$key]['task_data']['signature_person'] ?? '',
                                            'acknowledged_at' => $ackTime,
                                            'ticket_number' => $ticketReferenceNumber
                                        ]);
                        
                                        if (isset($task->customer->email) && filter_var($task->customer->email, FILTER_VALIDATE_EMAIL)) {
                                            $thisEmail = $task->customer->email;
                                            \App\Jobs\SendSignatureMailViaJob::dispatch($task, $ackTime, $signaturePath, $thisEmail, 'Skip Services');
                                        }
                        
                                        if (isset($task->location->email) && filter_var($task->location->email, FILTER_VALIDATE_EMAIL)) {
                                            $thisEmail = $task->location->email;
                                            if (!(isset($task->customer->email) && $task->customer->email == $thisEmail)) {
                                                \App\Jobs\SendSignatureMailViaJob::dispatch($task, $ackTime, $signaturePath, $thisEmail, 'Skip Services');
                                            }
                                        }

                                        if (isset($task->email) && filter_var($task->email, FILTER_VALIDATE_EMAIL)) {
                                            $thisEmail = $task->email;
                                            if (!((isset($task->location->email) && $task->location->email == $thisEmail) || (isset($task->customer->email) && $task->customer->email == $thisEmail))) {
                                                \App\Jobs\SendSignatureMailViaJob::dispatch($task, $ackTime, $signaturePath, $thisEmail, 'Skip Services');
                                            }
                                        }

                                        $dataToReturn[$task->id][] = $objectKey;
                                    }
                                }

                            }
                        }
                    }
                }

                \DB::commit();
                return response()->json(['success' => 'Data synchronization completed successfully.', 'sync_data' => $dataToReturn]);
            }

            $eTimestamp = microtime(true);
            self::apiStatusLog("SYNC API END: SUCCESS", '/sync', date('Y-m-d H:i:s'), $eTimestamp - $sTimestamp);

            \DB::rollBack();
            return response()->json(['error' => 'Please pass the data.']);
            /** END **/

        } catch (\Exception $e) {

            if (!empty($signaturesToUnlink)) {
                foreach ($signaturesToUnlink as $thisSignature) {
                    if (file_exists($thisSignature) && is_file($thisSignature)) {
                        @unlink($thisSignature);
                    }
                }
            }

            $eTimestamp = microtime(true);
            self::apiStatusLog("SYNC API END: ERROR", '/sync', date('Y-m-d H:i:s'), $eTimestamp - $sTimestamp);

            \DB::rollBack();
            \Log::channel('offline')->critical($e->getMessage() . ' ONLINE: ' . $e->getLine());

            return response()->json(['error' => Helper::$errorMessage]);
        }
    }

    public static function apiStatusLog($text = '', $apiEndpoint = '', $apiStartEndDate = '', $totalTime = null) {
        if (is_null($totalTime)) {
            \Log::channel('apiaccesslog')->critical("TEXT: {$text}; ENDPOINT: {$apiEndpoint}; START: {$apiStartEndDate}");
        } else {
            \Log::channel('apiaccesslog')->critical("TEXT: {$text}; ENDPOINT: {$apiEndpoint}; END: {$apiStartEndDate}; TOTAL: {$totalTime}");
        }
    }
}
