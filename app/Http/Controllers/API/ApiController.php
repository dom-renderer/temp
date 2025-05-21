<?php

namespace App\Http\Controllers\Api;

use App\Models\DepartmentUser;
use App\Models\Ticket;
use Illuminate\Support\Facades\Validator;
use App\Models\TaskDeviceInformation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\WorkflowAssignment;
use App\Models\WorkflowChecklist;
use App\Models\WorkflowTemplate;
use App\Models\ContentAnalytic;
use App\Models\RescheduledTask;
use App\Models\SubmissionTime;
use App\Models\ChecklistTask;
use Illuminate\Http\Request;
use App\Models\DeviceToken;
use App\Models\Designation;
use App\Models\Department;
use App\Models\RedoAction;
use App\Helpers\Helper;
use App\Models\Section;
use App\Models\Content;
use App\Models\Store;
use App\Models\Topic;
use App\Models\Tag;
use Carbon\Carbon;

class ApiController extends \App\Http\Controllers\Controller
{
    public function login(Request $request) {
        $validator = Validator::make($request->all(), [ 
            'phone_number' => 'required',
            'password' => 'required'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error'=>$errorString], 401);           
        }

        if (Auth::attempt(['phone_number' => $request->phone_number, 'password' => $request->password])) { 

            $user = Auth::user();
            unset($user->password);

            if ($user->status != 1) {
                return response()->json(['error' => 'Your account is disabled by the admin!'], 401);
            } else {
                $success = [
                    'token' => $user->createToken('MyLaravelApp')->accessToken,
                    'userId' => $user->id,
                    'userDetails' => $user,
                    'role' => auth()->user()->roles[0]->id
                ];

                if (in_array($user->roles[0]->id, [Helper::$roles['store-manager'], Helper::$roles['store-employee'], Helper::$roles['store-cashier']])) {
                    $success['working_stores'] = Store::select('id', 'name')->whereIn('id', Designation::where('user_id', $user->id)->where('type', 1)->pluck('type_id')->toArray())->get();
                }

                return response()->json(['success' => $success], 200);
            }
        } else {
            return response()->json(['error' => 'Unauthorised'], 401);
        }
    }

    public function stores(Request $request) {
        return response()->json(['success' => Store::with(['dom', 'storetype', 'modeltype'])->orderBy('name', 'ASC')->get()]);
    }
    public function departments(Request $request) {
        return response()->json(['success' => Department::orderBy('name', 'ASC')->get()]);
    }
    public function deviceToken(Request $request) {
        
        $validator = Validator::make($request->all(), [
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

        return response()->json(['success' => "Device token saved successfully."]);
    }

    public function removeDeviceToken(Request $request) {

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'token' => 'required'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error'=>$errorString], 401);
        }

        DeviceToken::where('user_id', $request->user_id)->where('token', $request->token)->update([
            'user_id' => null
        ]);
        
        return response()->json(['success' => "Device token removed from user successfully."]);
    }

    public function tasks(Request $request) {
        $page = $request->page > -1 ? $request->page : 0;
        $perPage = $request->record_per_page > 0 ? $request->record_per_page : 5;
        $skip = $page * $perPage;

        $filterCompending = $request->status;
        $filterFrom = date('Y-m-d H:i:s', strtotime($request->from));
        $filterTo = date('Y-m-d H:i:s', strtotime($request->to));

        $tasks = ChecklistTask::with(['restasks', 'submissionentries', 'redos', 'parent.parent', 'parent.user' => function ($builder) {
            return $builder->withTrashed();
        }])
        ->where(function ($innerBuilder) {
            $innerBuilder->whereHas('parent.parent', function ($query) {
                $query->where('checker_user_id', auth()->user()->id);
            })
            ->orWhereHas('parent', function ($query) {
                $query->where('user_id', auth()->user()->id);
            });
        })
        ->when(is_numeric($request->current_store_id) && $request->current_store_id > 0, function ($builder) {
            $builder->whereHas('parent.actstore', function ($query) {
                $query->where('branch_type', 1)
                ->where('branch_id', request('current_store_id'));
            });
        })
        ->when($filterCompending == 1, function ($builder) {
            return $builder->where('status', 0);
        })
        ->when($filterCompending == 2, function ($builder) {
            return $builder->where('status', 1);
        })
        ->when($filterCompending == 3, function ($builder) {
            return $builder->where('status', 2);
        })
        ->when($filterCompending == 4, function ($builder) {
            return $builder->where('status', 3);
        })
        
        ->when($request->task_type == 1, function ($builder) {
            $builder->whereHas('parent', function ($query) {
                $query->where('user_id', auth()->user()->id);
            });
        })
        ->when($request->task_type == 1 && in_array(request('filter_status'), ['PENDING', 'IN_PROGRESS', 'PENDING_VERIFICATION', 'VERIFIED', 'COMPLETED']), function ($builder) {
            if (request('filter_status') == 'PENDING') {
                $builder->where('status', 0);
            } else if (request('filter_status') == 'IN_PROGRESS') {
                $builder->where('status', 1);
            } else if (request('filter_status') == 'PENDING_VERIFICATION') {
                $builder->where('status', 2);
            } else if (request('filter_status') == 'VERIFIED') {
                $builder->where('status', 3)
                ->whereHas('parent.parent', function ($query) {
                    $query->where('checker_branch_id', '>', 0)
                    ->where('checker_user_id', '>', 0);
                });
            } else if (request('filter_status') == 'COMPLETED') {
                $builder->where('status', 3)
                ->whereHas('parent.parent', function ($query) {
                    $query->whereNull('checker_user_id');
                });
            }
        })


        ->when($request->task_type == 2, function ($builder) {
            $builder->whereHas('parent.parent', function ($query) {
                $query->where('checker_user_id', auth()->user()->id);
            });
        })

        ->when($request->task_type == 2 && in_array(request('filter_status'), ['PENDING_VERIFICATION', 'REASSIGNED', 'VERIFYING', 'VERIFIED']), function ($builder) {
            if (request('filter_status') == 'PENDING_VERIFICATION') {
                $builder->where('status', 2)
                ->whereDoesntHave('redos', function ($query) {
                    $query->where('status', [0,1]);
                });
            } else if (request('filter_status') == 'REASSIGNED') {
                $builder->where('status', 2)
                ->whereDoesntHave('redos', function ($query) {
                    $query->where('status', 1);
                })
                ->whereHas('redos', function ($query) {
                    $query->where('status', 0);
                });                
            } else if (request('filter_status') == 'VERIFYING') {
                $builder->where('status', 2)
                ->whereHas('redos', function ($query) {
                    $query->where('status', 1);
                });
            } else if (request('filter_status') == 'VERIFIED') {
                $builder->where('status', 3);
            }
        })

        ->when($request->task_type == 2 && !in_array(request('filter_status'), ['PENDING_VERIFICATION', 'REASSIGNED', 'VERIFYING', 'VERIFIED']), function ($builder) {
            $builder->where('status', 2);
        })


        ->scheduling();

        if (!empty($request->from) && !empty($request->to)) {
            $tasks = $tasks->where('date', '>=', date('Y-m-d H:i:s', strtotime($filterFrom)))
            ->where('date', '<=', date('Y-m-d H:i:s', strtotime($filterTo)));
        } else if (!empty($request->from) && empty($request->to)) {
            $tasks = $tasks->where('date', '>=', date('Y-m-d H:i:s', strtotime($filterFrom)));
        } else if (empty($request->from) && !empty($request->to)) {
            $tasks = $tasks->where('date', '<=', date('Y-m-d H:i:s', strtotime($filterTo)));
        } else {
            $tasks = $tasks->where(function ($where) {
                $where->where(\DB::raw("DATE_FORMAT(date, '%Y-%m-%d')"), date('Y-m-d'))
                ->orWhere(\DB::raw("DATE_FORMAT(date, '%Y-%m-%d')"), date('Y-m-d'));
            });
        }

        $taskCount = $tasks->clone()->count();

        $tasks = $tasks
        ->skip($skip)
        ->take($perPage)
        ->get()
        ->map(function ($el) {
            if ($el->status == 0) {
                $statusLabel = 'PENDING';
            } else if ($el->status == 1) {
                $statusLabel = 'IN-PROGRESS';
            } else if ($el->status == 2) {

                if (request('task_type') == 1 && request('filter_status') == 'PENDING_VERIFICATION') {
                    $statusLabel = 'PENDING-VERIFICATION';
                } else {
                    if (isset($el->parent->parent->checker_user_id) && isset($el->parent->parent->checker_branch_id)) {
                        if ($el->redos()->where('status', 1)->count() == 0 && $el->redos()->where('status', 0)->count() > 0) {
                            $statusLabel = 'REASSIGNED';
                        } else if ($el->redos()->where('status', 0)->count() == 0 && $el->redos()->where('status', 1)->count() == 0) {
                            $statusLabel = 'PENDING-VERIFICATION';
                        } else {
                            $statusLabel = 'VERIFYING';
                        }
                    } else {
                        $statusLabel = 'COMPLETED';
                    }
                }

            } else {
                if (isset($el->parent->parent->checker_user_id) && isset($el->parent->parent->checker_branch_id)) {
                    $statusLabel = 'VERIFIED';
                } else {
                    $statusLabel = 'COMPLETED';
                }
            }

            $tempTime = Helper::calculateTotalTime($el->id);

            return [
                'checklist_task_id' => $el->id,
                'checklist_id' => $el->parent->parent->checklist_id,
                'branch_type' => isset($el->parent->actstore->name) ? $el->parent->actstore->name : '',
                'branch_id' => $el->parent->branch_id,
                'user' => $el->parent->user,
                'checklist_title' => $el->parent->parent->checklist->name ?? '',
                'code' => $el->code,
                'schema_encoded' => isset($el->form) ? ($el->form) : null,
                'data' => isset($el->data) ? $el->data : null,
                'status' => $el->status,
                'is_point_checklist' => Helper::isPointChecklist(isset($el->form) ? $el->form : []),
                'status_label' => $statusLabel,
                'check_inout' => $el->submissionentries()->latest()->get()->toArray(),
                
                'reschedulings' => RescheduledTask::where('task_id', $el->id)->latest()->first(),                
                'do_not_allow_late_submission' => boolval($el->parent->parent->do_not_allow_late_submission),
                
                'date' => date('d-m-Y H:i', strtotime($el->date)),
                'should_start_at' => date('d-m-Y ', strtotime($el->date)) . (isset($el->parent->parent->start_at) ? date('H:i', strtotime($el->parent->parent->start_at)) : '00:00'),
                'should_completed_by' => date('d-m-Y ', strtotime($el->date)) . (isset($el->parent->parent->completed_by) ? date('H:i', strtotime($el->parent->parent->completed_by)) : '23:59'),
                'grace_start_time' => (isset($el->parent->parent->start_grace_time) ? date('d-m-Y H:i', strtotime(Helper::addGraceTime(date('Y-m-d H:i:s', strtotime(date('Y-m-d', strtotime($el->date)) . ' ' . (isset($el->parent->parent->start_at) ? $el->parent->parent->start_at : '23:59:59'))), $el->parent->parent->start_grace_time))) : null),
                'grace_end_time' => isset($el->parent->parent->end_grace_time) ? date('d-m-Y H:i', strtotime(Helper::addGraceTime(date('Y-m-d H:i:s', strtotime(date('Y-m-d', strtotime($el->date)) . ' ' . (isset($el->parent->parent->completed_by) ? $el->parent->parent->completed_by : '23:59:59'))), $el->parent->parent->end_grace_time))) : null,

                'grace_start' => isset($el->parent->parent->start_grace_time) ? $el->parent->parent->start_grace_time : null,
                'grace_end' => isset($el->parent->parent->end_grace_time) ? $el->parent->parent->end_grace_time : null,

                'should_complete_in' => isset($el->parent->parent->hours_required) ? $el->parent->parent->hours_required : null,
                'time_spent' => $tempTime,
                'remaining_time' => Helper::calculateRemainingTime(isset($el->parent->parent->hours_required) ? $el->parent->parent->hours_required : '00:00:00', $tempTime),
                
                'allow_rescheduling' => boolval(isset($el->parent->parent) ? $el->parent->parent->allow_rescheduling : 0),
                'can_reschedule_on_working_day' => boolval($el->parent->parent->allow_double_rescheduling),

                'excel_export' => route('task-export-excel', $el->id),
                'is_checker' => $el->parent->parent->checker_user_id == auth()->user()->id,
                'redo_action' => RedoAction::where('task_id', $el->id)->where('status', 0)->get()->toArray(),
                'pdf_export' => route('task-export-pdf', $el->id),

                'tickets' => Ticket::where('task_id', $el->id)->latest()->get()->map(function ($ticketEl) {
                    return [
                        'id' => $ticketEl->id,
                        'subject' => $ticketEl->subject,
                        'field_id' => $ticketEl->field_id,
                        'ticket_number' => $ticketEl->ticket_number,
                        'content' => $ticketEl->content,
                        'html' => $ticketEl->html,
                        'is_closed' => empty($ticketEl->completed_at) ? false : true,
                        'status' => $ticketEl->status->name ?? '',
                        'priority' => $ticketEl->priority->name ?? '',
                        'status_color' => $ticketEl->status->color ?? '',
                        'priority_color' => $ticketEl->priority->color ?? '',
                        'department' => $ticketEl->department->name ?? '',
                        'estimate_time' => $ticketEl->estimate_time,
                        'created_at' => $ticketEl->created_at,
                        'last_updated_at' => $ticketEl->updated_at,
                        'created_by' => isset($ticketEl->user) ? ($ticketEl->user->name . ' ' . $ticketEl->user->middle_name . ' ' . $ticketEl->user->last_name) : '',
                        'attachments' => $ticketEl->atchmnts->map(function ($tAttch) {
                            return [
                                'id' => $tAttch->id,
                                'url' => asset("storage/ticket-uploads/" . $tAttch->file)
                            ];
                        })->values()->toArray(),
                        'history' => $ticketEl->histories->map(function ($hEl) {
                            return [
                                'id' => $hEl->id,
                                'description' => $hEl->description,
                                'created_at' => $hEl->created_at
                            ];
                        })->sortByDesc('id')->values()->toArray(),
                        'comments' => $ticketEl->allcomments->map(function ($cEl) {
                            return [
                                'id' => $cEl->id,
                                'content' => $cEl->content,
                                'html' => $cEl->html,
                                'user' => isset($cEl->user) ? ($cEl->user->name . ' ' . $cEl->user->middle_name . ' ' . $cEl->user->last_name) : '',
                                'created_at' => $cEl->created_at
                            ];
                        })->values()->toArray()
                    ];
                })
            ];
        });
        
        $tasks = $tasks->toArray();

        return response()->json(['success' => $tasks, 'total_records' => $taskCount, 'page' => intval($page), 'record_per_page' => $perPage], 200); 
    }

    public function dashboard(Request $request) {

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'from' => 'required',
            'to' => 'required',
            'store_ids' => 'required|array',
            'store_ids.*' => 'exists:stores,id'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error'=>$errorString], 401);
        }

        $data = [
            'compliance_rate' => 0,
            'total_checklist' => 0,
            'pending_checklist' => 0,
            'flagged_items' => 0
        ];

        $stores = Store::select()->pluck('name', 'id')->toArray();

        if (!empty($stores)) {

            $totalChecklists = ChecklistTask::whereHas('parent', function ($builder) {
                $builder->where('branch_type', 1)->whereIn('branch_id', request('store_ids'))->where('user_id', request('user_id'));
            })
            ->scheduling()
            ->whereBetween(DB::raw("DATE_FORMAT(date, '%Y-%m-%d')"), [date('Y-m-d', strtotime($request->from)), date('Y-m-d', strtotime($request->to))]);

            $pendingChecklist = $totalChecklists->clone()->where('status', Helper::$status['pending']);
            $completedChecklist = $totalChecklists->clone()->where('status', Helper::$status['completed']);

            $data = [
                'compliance_rate' => number_format($pendingChecklist->count() > 0 ? (($completedChecklist->count() / $pendingChecklist->count()) * 100) : 0, 2) . '%',
                'total_checklist' => $totalChecklists->count(),
                'pending_checklist' => $pendingChecklist->count(),
                'flagged_items' => 0
            ];
        }

        return response()->json(['success' => $data]);
    }

    public function submission(Request $request) {
        TaskDeviceInformation::create([
            'eloquent' => ChecklistTask::class,
            'eloquent_id' => $request->task_id,
            'user_id' => auth()->check() ? auth()->user()->id : null,
            'device_model' => $request->device_model,
            'network_speed' => $request->network_speed,
            'device_version' => $request->device_version
        ]);

        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:checklist_tasks,id',
            'status' => 'required|in:1,2',
            'data' => 'required'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error' => $errorString], 401);
        }

        $task = ChecklistTask::find($request->task_id);

        if ($task->status == Helper::$status['in-verification']) {
            return response()->json(['error' => 'This Checklist already submitted.']);
        }

        if ($task->type == 0 && $task->parent->parent->frequency_type != 12) {
            if ($task->parent->parent->do_not_allow_late_submission == 1) {
                $toBeCompared = date('Y-m-d H:i');
                
                $fromToBeCompared = date('Y-m-d', strtotime($task->date)) . ' ' . date('H:i', strtotime($task->parent->parent->start_at));
                $toToBeCompared = date('Y-m-d', strtotime($task->date)) . ' ' . date('H:i', strtotime($task->parent->parent->completed_by));

                $gStart = explode(':', empty($task->parent->parent->start_grace_time) ? '00:00:00' : $task->parent->parent->start_grace_time);
                $gEnd = explode(':', empty($task->parent->parent->end_grace_time) ? '00:00:00' : $task->parent->parent->end_grace_time);

                $fromToBeCompared = Carbon::parse($fromToBeCompared)->addHours($gStart[0])->addMinutes($gStart[1])->addSeconds($gStart[2]);
                $toToBeCompared = Carbon::parse($toToBeCompared)->addHours($gEnd[0])->addMinutes($gEnd[1])->addSeconds($gEnd[2]);

                if (Carbon::parse($fromToBeCompared)->gte($toToBeCompared)) {
                    $toToBeCompared = Carbon::parse($toToBeCompared)->addDay()->format('Y-m-d H:i');
                }

                if (!(Carbon::parse($toBeCompared)->gte($fromToBeCompared) && Carbon::parse($toBeCompared)->lte($toToBeCompared))) {
                    return response()->json(['error' => 'The time has passed for the submission of this checklist.']);
                }
            }
        }

        if (!file_exists(storage_path('app/public/workflow-task-uploads'))) {
            mkdir(storage_path('app/public/workflow-task-uploads'), 0777, true);
        }

        $data = json_decode($request->data, true);

        foreach ($data as &$dt) {
            if (array_key_exists('isFile', $dt) && $dt['isFile'] == true) {
                if (is_array($dt['value'])) {
                    foreach ($dt['value'] as &$tempRow) {
                        if (strpos($tempRow, 'SIGN-20') !== false) {
                            continue;
                        }
                        $tempRow = Helper::downloadBase64File($tempRow, ('SIGN-' . date('YmdHis') . uniqid()), storage_path('app/public/workflow-task-uploads'));
                    }
                } else {
                    if (strpos($dt['value'], 'SIGN-20') !== false) {
                        continue;
                    }
                    $dt['value'] = Helper::downloadBase64File($dt['value'], ('SIGN-' . date('YmdHis') . uniqid()), storage_path('app/public/workflow-task-uploads'));
                }
            }
        }

        if (empty($task->started_at)) {
            $task->started_at = now();
        }

        $task->data = $data;

        if ($task->type == 0 && isset($task->parent->parent->checker_branch_id) && isset($task->parent->parent->checker_user_id)) {
            $task->status = $request->status;
        } else {
            if ($request->status == 2) {
                $task->status = 3;
            } else {
                $task->status = $request->status;
            }
        }

        if ($task->type == 0 && $request->status == 2) {
            self::dispatchNotifications($task);
        }


        $task->save();

        if ($request->status == Helper::$status['in-verification']) {
            $task = ChecklistTask::find($request->task_id);
            $task->completion_date = now();

            if ($task->type == 1) {
                $task->status = Helper::$status['completed'];
            }

            $task->save();
        }

        return response()->json(['success' => 'Checklist submitted successfully.', 'data' => $data]);
    }

    public static function dispatchNotifications($task) {
        $task = ChecklistTask::with(['parent.parent.checklist.presetemplates'])->where('id', $task->id)->first();

        try {
            if (isset($task->parent->parent->checklist->presetemplates) && is_iterable($task->parent->parent->checklist->presetemplates)) {
                foreach ($task->parent->parent->checklist->presetemplates()->with('ntemp')->where('type', 7)->get() as $notification) {
                    if (isset($notification->ntemp->id)) {
                        if ($notification->ntemp->type === 0) {

                            if (isset($task->parent)) {
                                $extra = $task->parent;

                                $user = \App\Models\User::find($task->parent->parent->checker_user_id);
                                $location = Store::find($extra->store_id);
                                $checklist = \App\Models\DynamicForm::find($task->parent->parent->checklist_id);

                                $content = str_replace(array_keys(Helper::$notificationTemplatePlaceholders), [
                                    isset($user->id) ? ("{$user->name} {$user->middle_name} {$user->last_name}") : 'N/A',
                                    $user->username ?? 'N/A',
                                    $user->phone_number ?? 'N/A',
                                    $user->email ?? 'N/A',
                                    $location->name ?? 'N/A',
                                    $checklist->name ?? 'N/A',
                                    'N/A'
                                ], $notification->ntemp->content);

                                $title = str_replace(array_keys(Helper::$notificationTemplatePlaceholders), [
                                    isset($user->id) ? ("{$user->name} {$user->middle_name} {$user->last_name}") : 'N/A',
                                    $user->username ?? 'N/A',
                                    $user->phone_number ?? 'N/A',
                                    $user->email ?? 'N/A',
                                    $location->name ?? 'N/A',
                                    $checklist->name ?? 'N/A',
                                    'N/A'
                                ], $notification->ntemp->title);

                                \Illuminate\Support\Facades\Mail::to($user->email)
                                ->send(new \App\Mail\EscalationMail($title, $content));
                            }

                        } else if ($notification->ntemp->type == 1) {

                            if (isset($task->parent)) {
                                $extra = $task->parent;

                                $user = \App\Models\User::find($task->parent->parent->checker_user_id);
                                $location = Store::find($extra->store_id);
                                $checklist = \App\Models\DynamicForm::find($task->parent->parent->checklist_id);

                                $content = str_replace(array_keys(Helper::$notificationTemplatePlaceholders), [
                                    isset($user->id) ? ("{$user->name} {$user->middle_name} {$user->last_name}") : 'N/A',
                                    $user->username ?? 'N/A',
                                    $user->phone_number ?? 'N/A',
                                    $user->email ?? 'N/A',
                                    $location->name ?? 'N/A',
                                    $checklist->name ?? 'N/A',
                                    'N/A'
                                ], $notification->ntemp->content);

                                $title = str_replace(array_keys(Helper::$notificationTemplatePlaceholders), [
                                    isset($user->id) ? ("{$user->name} {$user->middle_name} {$user->last_name}") : 'N/A',
                                    $user->username ?? 'N/A',
                                    $user->phone_number ?? 'N/A',
                                    $user->email ?? 'N/A',
                                    $location->name ?? 'N/A',
                                    $checklist->name ?? 'N/A',
                                    'N/A'
                                ], $notification->ntemp->title);

                                $deviceTokens = [];
                                if (isset($user->id)) {
                                    $deviceTokens = DeviceToken::where('user_id', $user->id)->pluck('token')->toArray();
                                }
                
                                if (!empty($deviceTokens)) {
                                    Helper::sendPushNotification($deviceTokens, [
                                        'title' => $title,
                                        'description' => $content
                                    ]);
                                }
                            }

                        }
                    }
                }
            }
        } catch (\Exception $e) {}
    }

    public function workflowListing(Request $request) {
        $allWorkflows = WorkflowAssignment::selectRaw("id, workflow_id as workflow_template_id, name, start_date, end_date, created_at")
        ->whereHas('specificclist', function ($builder) {
            return $builder->where('user_id', auth()->user()->id);
        })
        ->get()
        ->toArray();

        $data = [];
        $templateNames = WorkflowTemplate::select('id', 'name')->pluck('name', 'id')->toArray();

        foreach ($allWorkflows as $row) {

            $tasks = ChecklistTask::withTrashed()->whereHas('workflowclist', function ($builder) use ($row) {
                $builder->withTrashed()
                ->where('user_id', auth()->user()->id)
                ->where('workflow_assignment_id', $row['id']);
            })
            ->get();

            $final = $total = $filled = 0;

            foreach ($tasks as $task) {
                $total += Helper::getCountHavingKey($task['form'] ?? [], 'name');
                $filled += Helper::getCountHavingKey($task['data'] ?? [], 'name');                    
            }

            try {
                if ($total > 0) {
                    $final = ($filled / $total) * 100;
                }
            } catch (\Exception $e) {}

            $data[] = [
                'id' => $row['id'],
                'workflow_template_id' => $row['workflow_template_id'],
                'name' => $row['name'],
                'template_name' => $templateNames[$row['workflow_template_id']] ? $templateNames[$row['workflow_template_id']] : '',
                'start_date' => $row['start_date'],
                'end_date' => $row['end_date'],
                'created_at' => $row['created_at'],
                'completion_rate' => number_format($final, 2)
            ];
        }

        return response()->json(['success' => $data]);
    }

    public function sectionListing(Request $request) {
        $validator = Validator::make($request->all(), [
            'workflow_id' => 'required|exists:workflow_assignments,id'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error' => $errorString], 401);
        }

        $allSections = WorkflowChecklist::where('user_id', auth()->user()->id)
        ->where('workflow_assignment_id', $request->workflow_id)
        ->groupBy('section_id')
        ->pluck('section_id')
        ->toArray();

        $sData = Section::selectRaw("id, parent_id as parent_section_id, name, slug, created_at")->whereIn('id', $allSections)->get();
        $data = [];


        foreach ($sData as $row) {

            $tasks = ChecklistTask::withTrashed()->whereHas('workflowclist', function ($builder) use ($row) {
                $builder->withTrashed()
                ->where('user_id', auth()->user()->id)
                ->where('workflow_assignment_id', request('workflow_id'))
                ->where('section_id', $row['id']);
            })
            ->get();

            $final = $total = $filled = 0;

            foreach ($tasks as $task) {
                $total += Helper::getCountHavingKey($task['form'] ?? [], 'name');
                $filled += Helper::getCountHavingKey($task['data'] ?? [], 'name');                    
            }

            try {
                if ($total > 0) {
                    $final = ($filled / $total) * 100;
                }
            } catch (\Exception $e) {}

            $data[] = [
                'id' => $row['id'],
                'parent_section_id' => $row['parent_section_id'],
                'name' => $row['name'],
                'slug' => $row['slug'],
                'created_at' => $row['created_at'],
                'completion_rate' => number_format($final, 2)
            ];
        }

        return response()->json(['success' => $data]);
    }

    public function workflowTaskListing(Request $request) {
        $validator = Validator::make($request->all(), [
            'workflow_id' => 'required|exists:workflow_assignments,id',
            'section_id' => 'required|exists:sections,id'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error' => $errorString], 401);
        }

        $filterCompending = $request->status;
        $filterFrom = date('Y-m-d H:i:s', strtotime($request->from));
        $filterTo = date('Y-m-d H:i:s', strtotime($request->to));

        $tasks = ChecklistTask::whereHas('workflowclist', function ($builder) {
            return $builder->where('user_id', auth()->user()->id)
            ->where('workflow_assignment_id', request('workflow_id'))
            ->where('section_id', request('section_id'));
        })
        ->with([
            'workflowclist' => function ($builder) {
                return $builder->withTrashed();
            },
            'workflowclist.wftmp' => function ($builder) {
                return $builder->withTrashed();
            },
            'workflowclist.sec' => function ($builder) {
                return $builder->withTrashed();
            },
            'workflowclist.wftmpasgmt' => function ($builder) {
                return $builder->withTrashed();
            },
            'workflowclist.clist' => function ($builder) {
                return $builder->withTrashed();
            },
            'workflowclist.usr' => function ($builder) {
                return $builder->withTrashed();
            },
            'workflowclist.store' => function ($builder) {
                return $builder->withTrashed();
            },
            'workflowclist.dept' => function ($builder) {
                return $builder->withTrashed();
            }
        ])
        ->when($filterCompending == 1, function ($builder) {
            return $builder->where('status', 0);
        })
        ->when($filterCompending == 2, function ($builder) {
            return $builder->where('status', 1);
        })
        ->when($filterCompending == 3, function ($builder) {
            return $builder->where('status', 3);
        })
        ->when(!empty($request->from), function ($builder) use ($filterFrom) {
            return $builder->where('date', '>=', date('Y-m-d H:i:s', strtotime($filterFrom)));
        })
        ->when(!empty($request->to), function ($builder) use ($filterTo) {
            return $builder->where('date', '<=', date('Y-m-d H:i:s', strtotime($filterTo)));
        })
        ->workflows()
        ->get()
        ->map(function ($el) {
            return [
                'task_id' => $el->id,
                'checklist_id' => $el->checklist_id,
                'branch_type' => isset($el->workflowclist->branch_type) ? ($el->workflowclist->branch_type == 1 ? 'Store' : 'Department') : '-',
                'branch_id' => $el->workflowclist->branch_id ?? null,
                'user' => $el->workflowclist->user_id ?? null,
                'checklist_title' => $el->workflowclist->clist->name ?? '',
                'assignment_name' => $el->workflowclist->wftmpasgmt->name ?? '',
                'code' => $el->code,
                'schema_encoded' => $el->form,
                'data' => $el->data,
                'status' => $el->status == 3 ? 2 : $el->status,
                'date' => date('d-m-Y H:i', strtotime($el->date)),
                'excel_export' => route('task-export-excel', $el->id),
                'pdf_export' => route('task-export-pdf', $el->id)
            ];
        });

        return response()->json(['success' => $tasks]);
    }

    public function taskVariables(Request $request) {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:checklist_tasks,id'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error'=>$errorString], 401);
        }

        $task = ChecklistTask::where('id', $request->task_id)->first();

        $varients = Helper::categorizePoints($task->data);

        $total = count(Helper::selectPointsQuestions($task->data));
        $toBeCounted = $total;

        $failed = abs(array_sum(array_column($varients['negative'], 'value')));
        $achieved = $toBeCounted - abs($failed);
        
        if ($failed <= 0) {
            $achieved = array_sum(array_column($varients['positive'], 'value'));
        }
        
        if ($toBeCounted > 0) {
            $percentage = number_format(($achieved / $toBeCounted) * 100, 2);
        } else {
            $percentage = 0;
        }

        return response()->json(['success' => 'Variables fetched successfully.', 'data' => [
            'total_points_question' => $total,
            'total_points_question_without_na' => $toBeCounted,
            'total_passed_count' => $achieved,
            'total_na_count' => count($varients['na']),
            'total_failed_count' => count($varients['negative']),
            'final_result' => $percentage > 50 ? "Pass" : "Fail",
            'percentage' => "$percentage%"
        ]]);
    }

    public function approveDecline(Request $request) {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:checklist_tasks,id',
            'class' => 'required',
            'action' => 'required'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error'=>$errorString], 401);
        }

        $task = ChecklistTask::find($request->task_id);

        \DB::beginTransaction();

        try {
            $json = $task->data;

            foreach ($json as &$item) {
                if (isset($item->className) && $item->className === $request->class) {
                    if ($request->action == 'approve') {
                        $item->approved = 'yes';
                    } else if ($request->action == 'decline') {
                        $item->approved = 'no';
                    }
                }
            }

            if ($request->action == 'decline') {
                $redoActionExists = RedoAction::where('task_id', $request->task_id)
                ->where('field_id', $request->class);
    
                if ($redoActionExists->exists()) {
                    $redoActionExists->update([
                        'title' => $request->title,
                        'remarks' => $request->remarks,
                        'status' => 0,
                        'start_at' => date('Y-m-d H:i:s', strtotime($request->start_at)),
                        'completed_by' => date('Y-m-d H:i:s', strtotime($request->completed_by)),
                        'do_not_allow_late_submission' => $request->do_not_allow_late_submission
                    ]);
                } else {
                    RedoAction::create([
                        'task_id' => $request->task_id,
                        'field_id' => $request->class,
                        'title' => $request->title,
                        'remarks' => $request->remarks,
                        'start_at' => date('Y-m-d H:i:s', strtotime($request->start_at)),
                        'completed_by' => date('Y-m-d H:i:s', strtotime($request->completed_by)),
                        'do_not_allow_late_submission' => $request->do_not_allow_late_submission
                    ]);
                }
            }

            $task->data = $json;
            $task->save();

            \DB::commit();
            return response()->json(['success' => 'Updated successfully']);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('API CHECKER VERIFICATION: ' . $e->getMessage() . ' ON LINE ' . $e->getLine());
            return response()->json(['error' => 'Something went wrong']);
        }
    }

    public function redoActionTasks(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error'=>$errorString], 401);
        }

        $allTasksId = [];
        $filterFrom = date('Y-m-d H:i:s', strtotime($request->from));
        $filterTo = date('Y-m-d H:i:s', strtotime($request->to));

        $redoActions = RedoAction::with(['task.parent.parent'])
        ->whereHas('task.parent', function ($builder) {
            $builder->where('user_id', request('user_id'));
        })
        ->where('status', 0);

        $allTasksId = $redoActions->pluck('task_id')->toArray();

        $tasks = ChecklistTask::with(['parent.parent', 'parent.user' => function ($builder) {
            return $builder->withTrashed();
        }])
        ->when(is_numeric($request->current_store_id) && $request->current_store_id > 0, function ($builder) {
            $builder->whereHas('parent.parent', function ($query) {
                $query->where('branch_type', 1)
                ->where('branch_id', request('current_store_id'));
            });
        })
        ->when(!empty($allTasksId), function ($builder) use ($allTasksId) {
            $builder->whereIn('id', $allTasksId);
        })
        ->when(empty($allTasksId), function ($builder) {
            $builder->where('id', 0);
        })
        ->scheduling();

        if (!empty($request->from) && !empty($request->to)) {
            $tasks = $tasks->where('date', '>=', date('Y-m-d H:i:s', strtotime($filterFrom)))
            ->where('date', '<=', date('Y-m-d H:i:s', strtotime($filterTo)));
        } else if (!empty($request->from) && empty($request->to)) {
            $tasks = $tasks->where('date', '>=', date('Y-m-d H:i:s', strtotime($filterFrom)));
        } else if (empty($request->from) && !empty($request->to)) {
            $tasks = $tasks->where('date', '<=', date('Y-m-d H:i:s', strtotime($filterTo)));
        }

        $tasks = $tasks
        ->get()
        ->map(function ($el) {
            return [
                'checklist_task_id' => $el->id,
                'checklist_id' => $el->parent->parent->checklist_id,
                'branch_type' => isset($el->parent->actstore->name) ? $el->parent->actstore->name : '',
                'branch_id' => $el->parent->branch_id,
                'user' => $el->parent->user,
                'checklist_title' => $el->parent->parent->checklist->name ?? '',
                'code' => $el->code,
                'do_not_allow_late_submission' => $el->do_not_allow_late_submission,
                'date' => date('d-m-Y H:i', strtotime($el->date)),
                
                'should_start_at' => isset($el->parent->parent->start_at) ? date('H:i', strtotime($el->parent->parent->start_at)) : '00:00',
                'should_completed_by' => isset($el->parent->parent->completed_by) ? date('H:i', strtotime($el->parent->parent->completed_by)) : '23:59',

                'grace_start_time' => isset($el->parent->parent->start_grace_time) ? Helper::addGraceTime(date('d-m-Y H:i:s', strtotime($el->date . ' ' . (isset($el->parent->parent->start_at) ? $el->parent->parent->start_at : '23:59:59'))), $el->parent->parent->start_grace_time) : null,
                'grace_end_time' => isset($el->parent->parent->end_grace_time) ? Helper::addGraceTime(date('d-m-Y H:i:s', strtotime($el->date . ' ' . (isset($el->parent->parent->completed_by) ? $el->parent->parent->completed_by : '23:59:59'))), $el->parent->parent->end_grace_time) : null,

                'should_complete_in' => isset($el->parent->parent->hours_required) ? $el->parent->parent->hours_required : null,

                'allow_rescheduling' => isset($el->parent->parent) ? $el->parent->parent->allow_rescheduling : 0,
            ];
        });
        
        $tasks = $tasks->toArray();

        return response()->json(['success' => $tasks], 200); 
    }

    public function getRedoActions(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'task_id' => 'required|exists:checklist_tasks,id'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error'=>$errorString], 401);
        }

        $page = $request->page > -1 ? $request->page : 0;
        $perPage = $request->record_per_page > 0 ? $request->record_per_page : 5;
        $skip = $page * $perPage;

        $tasks = RedoAction::with(['task'])
        ->whereHas('task.parent', function ($builder) {
            $builder->where('user_id', request('user_id'));
        })
        ->whereHas('task', function ($builder) {
            $builder->where('id', request('task_id'));
        })
        ->when($request->filter_status == 'PENDING' || $request->filter_status == 'COMPLETED', function ($builder) {
            if (request('filter_status') == 'PENDING') {
                $builder->where('status', 0);
            } else {
                $builder->where('status', 1);
            }
        });

        $taskCount = $tasks->clone()->count();

        $tasks = $tasks
        ->orderBy('status', 'ASC')
        ->skip($skip)
        ->take($perPage)
        ->get()
        ->map(function ($el) {
            $matchedItems = [];

            foreach (is_array($el->task->form) ? $el->task->form : [] as $section) {
                foreach ($section as $item) {
                    if (isset($item->className) && $item->className === $el->field_id) {
                        $item->last_submission = collect($el->task->data)
                        ->where('className', $item->className)
                        ->where('name', $item->name)
                        ->first();

                        $matchedItems[] = $item;
                    }
                }
            }

            return [
                'id' => $el->id,
                'task_id' => $el->task->id,
                'task_code' => $el->task->code,
                'class' => $el->field_id,
                'title' => $el->title,
                'remarks' => $el->remarks,
                'start_at' => $el->start_at,
                'completed_by' => $el->completed_by,
                'do_not_allow_late_submission' => $el->do_not_allow_late_submission,
                'status' => $el->status,
                'status_label' => $el->status == 0 ? 'PENDING' : 'COMPLETED',
                'fields_todo' => $matchedItems
            ];
        });
        
        $tasks = $tasks->toArray();

        return response()->json(['success' => $tasks, 'total_records' => $taskCount, 'page' => intval($page), 'record_per_page' => $perPage], 200); 
    }

    public function submitRedo(Request $request) {
        TaskDeviceInformation::create([
            'eloquent' => ChecklistTask::class,
            'eloquent_id' => $request->task_id,
            'user_id' => auth()->check() ? auth()->user()->id : null,
            'device_model' => $request->device_model,
            'network_speed' => $request->network_speed,
            'device_version' => $request->device_version,
            'resubmission' => 1
        ]);

        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:checklist_tasks,id'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error'=>$errorString], 401);
        }

        if (is_string($request->data)) {
            $decodedJson = json_decode($request->data, true);
        } else {
            $decodedJson = $request->data;
        }

        if (empty($decodedJson)) {
            return response()->json(['error' => 'You must have to submit atleast a field']);
        }

        if (!file_exists(storage_path('app/public/workflow-task-uploads'))) {
            mkdir(storage_path('app/public/workflow-task-uploads'), 0777, true);
        }

        \DB::beginTransaction();

        try {

            $task = ChecklistTask::find($request->task_id);
            $json = $task->data;

            foreach ($decodedJson as $row) {
                RedoAction::where('id', $row['id'])->update(['status' => 1]);

                $tempArr = $row['data'];

                foreach ($tempArr as &$dt) {
                    if (array_key_exists('isFile', $dt) && $dt['isFile'] == true) {
                        if (is_array($dt['value'])) {
                            foreach ($dt['value'] as &$tempRow) {
                                if (strpos($tempRow, 'SIGN-20') !== false) {
                                    continue;
                                }
                                $tempRow = Helper::downloadBase64File($tempRow, ('SIGN-' . date('YmdHis') . uniqid()), storage_path('app/public/workflow-task-uploads'));
                            }
                        } else {
                            if (strpos($dt['value'], 'SIGN-20') !== false) {
                                continue;
                            }
                            $dt['value'] = Helper::downloadBase64File($dt['value'], ('SIGN-' . date('YmdHis') . uniqid()), storage_path('app/public/workflow-task-uploads'));
                        }
                    }
                }

                foreach ($json as &$item) {
                    if (isset($item->className) && $item->className === $row['class']) {
                        if (is_iterable($tempArr)) {
                            foreach ($tempArr as $k => $v) {
                                if ((is_array($item) ? $item['name'] : $item->name) == (is_array($v) ? $v['name'] : $v->name)) {
                                    $item = $v;
                                }
                            }
                        }
                    }
                }
            }

            $task->data = $json;
            $task->save();

            \DB::commit();
            return response()->json(['success' => 'Assignment submitted successfully']);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('REASSIGNMENT FAILURE: ' . $e->getMessage() . ' ON LINE : ' . $e->getLine());
            return response()->json(['error' => 'You must have to submit atleast a field']);
        }
    }

    public function reassignmentTasks(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error'=>$errorString], 401);
        }

        $page = $request->page > -1 ? $request->page : 0;
        $perPage = $request->record_per_page > 0 ? $request->record_per_page : 5;
        $skip = $page * $perPage;

        $tasks = ChecklistTask::with(['parent.parent'])
        ->whereHas('redos', function ($builder) {
            $builder->where('status', 0);
        })
        ->when($request->filter_status == 'PENDING' || $request->filter_status == 'COMPLETED', function ($builder) {
            if (request('filter_status') == 'PENDING') {
                $builder->whereHas('redos', function ($innerBuilder) {
                    $innerBuilder->where('status', 0);
                });
            } else {
                $builder->whereDoesntHave('redos', function ($innerBuilder) {
                    $innerBuilder->where('status', 0);
                });
            }
        })
        ->where(function ($innerBuilder) {
            $innerBuilder->whereHas('parent', function ($query) {
                $query->where('user_id', auth()->user()->id);
            });
        })
        ->scheduling();

        $taskCount = $tasks->clone()->count();

        $tasks = $tasks
        ->skip($skip)
        ->take($perPage)
        ->get()
        ->map(function ($el) {
            $reDoArray = [];
            $tempPage = null;
            
            foreach ($el->redos as $x) {
                $matchedItems = [];

                foreach (is_array($el->form) ? $el->form : [] as $section) {
                    foreach ($section as $item) {
                        if (isset($item->className) && $item->className === $x->field_id) {
                            $item->last_submission = collect($el->data)
                            ->where('className', $item->className)
                            ->where('name', $item->name)
                            ->first();
                            
                            $matchedItems[] = $item;

                            if ($tempPage == null) {
                                $tempPage = isset($item->last_submission->page) ? $item->last_submission->page : 0;
                            }
                        }
                    }
                }

                $reDoArray[$tempPage] = [
                    'class' => $x->field_id,
                    'title' => $x->title,
                    'remarks' => $x->remarks,
                    'start_at' => $x->start_at,
                    'completed_by' => $x->completed_by,
                    'do_not_allow_late_submission' => $x->do_not_allow_late_submission,
                    'status' => $x->status,
                    'status_label' => $x->status == 0 ? 'PENDING' : 'COMPLETED',
                    'fields_todo' => array_values($matchedItems),
                    'page' => intval($tempPage)
                ];

                $tempPage = null;
            }


            if ($el->status == 0) {
                $statusLabel = 'PENDING';
            } else if ($el->status == 1) {
                $statusLabel = 'IN-PROGRESS';
            } else if ($el->status == 2) {

                if (request('task_type') == 1 && request('filter_status') == 'PENDING_VERIFICATION') {
                    $statusLabel = 'PENDING-VERIFICATION';
                } else {
                    if (isset($el->parent->checker_user_id) && isset($el->parent->checker_branch_id)) {
                        if ($el->redos()->where('status', 1)->count() == 0 && $el->redos()->where('status', 0)->count() > 0) {
                            $statusLabel = 'REASSIGNED';
                        } else if ($el->redos()->where('status', 0)->count() == 0 && $el->redos()->where('status', 1)->count() == 0) {
                            $statusLabel = 'PENDING-VERIFICATION';
                        } else {
                            $statusLabel = 'VERIFYING';
                        }
                    } else {
                        $statusLabel = 'COMPLETED';
                    }
                }

            } else {
                if (isset($el->parent->parent->checker_user_id) && isset($el->parent->parent->checker_branch_id)) {
                    $statusLabel = 'VERIFIED';
                } else {
                    $statusLabel = 'COMPLETED';
                }
            }

            return [
                'checklist_scheduling_id' => $el->parent->parent->id,
                'checklist_scheduling_extra_id' => $el->parent->id,
                'checklist_id' => $el->parent->parent->checklist_id,
                'task_id' => $el->id,
                'reassignment_data' => array_values($reDoArray),
                'branch_type' => (isset($el->parent->actstore->name) ? $el->parent->actstore->name : ''),
                'branch_id' => $el->parent->branch_id,
                'user' => $el->parent->user,
                'checklist_title' => $el->parent->checklist->name ?? '',
                'code' => $el->code,
                'is_point_checklist' => Helper::isPointChecklist(isset($el->form) ? $el->form : []),
                'status_label' => $statusLabel,
            ];
        });
        
        $tasks = $tasks->toArray();

        return response()->json(['success' => $tasks, 'total_records' => $taskCount, 'page' => intval($page), 'record_per_page' => $perPage], 200); 
    }

    public function submissionDurationCount(Request $request) {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:checklist_tasks,id',
            'type' => 'required|in:1,2',
            'timestamp' => 'required'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error' => $errorString], 401);
        }

        SubmissionTime::create([
            'task_id' => $request->task_id,
            'type' => $request->type,
            'timestamp' => date('Y-m-d H:i:s', strtotime($request->timestamp))
        ]);

        return response()->json(['success' => $request->type == 1 ? 'Started Successfully' : 'Paused Successfully']);
    }

    public function rescheduleTask(Request $request) {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:checklist_tasks,id',
            'remarks' => 'required',
            'date' => 'required'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error' => $errorString], 401);
        }

        $last = RescheduledTask::where('task_id', $request->task_id)->latest()->first();

        if ($last && $last->status === 0) {
            return response()->json(['error' => 'Rescheduling approval is already in pending.']);
        }

        $resDate = date('Y-m-d H:i:s', strtotime($request->date));

        RescheduledTask::create([
            'task_id' => $request->task_id,
            'remarks' => $request->remarks,
            'date' => $resDate
        ]);

        $task = ChecklistTask::find($request->task_id);
        \App\Jobs\NotificationRescheduleRequest::dispatch($task, $resDate);

        return response()->json(['success' => 'Rescheduling request has been sent.']);
    }

    public function rescheduleTaskListing(Request $request) {
        $validator = Validator::make($request->all(), [
            'user' => 'required|exists:users,id',
            'type' => 'required|in:1,2'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error' => $errorString], 401);
        }

        $list = RescheduledTask::query()
        ->when(!empty($request->store), function ($builder) {
            $builder->whereHas('task.parent', function ($innerBuilder) {
                $innerBuilder->where('store_id', request('store'));
            });
        })
        ->when($request->status === "0", function ($builder) {
            $builder->where('status', 0);
        })
        ->when($request->status === "1", function ($builder) {
            $builder->where('status', 1);
        })
        ->when($request->status === "2", function ($builder) {
            $builder->where('status', 2);
        })
        ->when($request->type == 1, function ($builder) {
            $builder->whereHas('task.parent', function ($innerBuilder) {
                $innerBuilder->where('user_id', request('user'));
            });
        },function ($builder) {
            $builder->whereHas('task.parent.parent', function ($innerBuilder) {
                $innerBuilder->where('checker_user_id', request('user'));
            });
        })
        ->get()
        ->map(function ($el) {
            return [
                'rescheduling_id' => $el->id,
                'remarks' => $el->remarks,
                'status' => $el->status == 1 ? 'ACCEPTED' : ($el->status == 2 ? 'REJECTED' : 'PENDING'),
                'new_rescheduling_date' => date('d-m-Y H:i', strtotime($el->date)),
                'task' => collect([$el->task])->map(function ($inEl, $val) {
                    return [
                        'id' => $inEl->id,
                        'code' => $inEl->code,
                        'date' => $inEl->date,
                        'checklist_name' => $inEl->parent->parent->checklist->name ?? null,
                        'store_name' => $inEl->parent->actstore->name ?? null,
                        'maker_user' => $inEl->parent->user_id ?? null,
                        'check_user' => $inEl->parent->parent->checker_user_id ?? null
                    ];
                })->first()
            ];
        });

        return response()->json(['success' => $list]);
    }

    public function rescheduleTaskReschedule(Request $request) {
        $validator = Validator::make($request->all(), [
            'rescheduling_id' => 'required|exists:rescheduled_tasks,id',
            'status' => 'required|in:1,2'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error' => $errorString], 401);
        }

        RescheduledTask::where('id' , $request->rescheduling_id)->update([
            'status' => $request->status
        ]);

        $task = RescheduledTask::where('id' , $request->rescheduling_id)->first();

        if ($request->status == 1) {
            ChecklistTask::where('id', $task->task_id ?? null)->update([
                'date' => $task->date ?? date('Y-m-d H:i:s'),
                'status' => 0
            ]);

            $task2 = ChecklistTask::find($task->task_id ?? null);
            \App\Jobs\NotificationRescheduleApproval::dispatch($task2, $task->date ?? date('Y-m-d H:i:s'));
        } else {
            $task2 = ChecklistTask::find($task->task_id ?? null);
            \App\Jobs\NotificationRescheduleRejection::dispatch($task2, $task->date ?? date('Y-m-d H:i:s'));
        }

        return response()->json(['success' => 'Rescheduling request has response has been sent.']);
    }

    public function taskMonthView(Request $request) {
        $validator = Validator::make($request->all(), [
            'user' => 'required|exists:users,id',
            'month' => 'required|array',
            'month.*' => 'in:1,2,3,4,5,6,7,8,9,10,11,12',
            'year' => 'required|numeric|min:2024|max:2050'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error' => $errorString], 401);
        }

        $months = $request->month;

        $monthA = min($months);
        $monthB = max($months);
        $year = $request->year;

        $start = Carbon::create($year, $monthA, 1);
        $end = Carbon::create($year, $monthB, 1)->endOfMonth();

        $tasks = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {

            $temp = ChecklistTask::with(['parent.parent.checklist'])
            ->whereHas('parent', function ($builder) {
                $builder->where('user_id', request('user'));
            })
            ->where(DB::raw("DATE(date)"), $date->format('Y-m-d'))
            ->get();
            
            if (!$temp->isEmpty()) {
                $tasks[] = [
                    'task_id' => $temp[0]->id,
                    'task_code' => $temp[0]->code,
                    'task_checklist' => $temp[0]->parent->parent->checklist->name ?? '',
                    'task_store' => $temp[0]->parent->actstore->name ?? '',
                    'date' => $date->format('d-m-Y'),
                    'total' => count($temp)
                ];
            } else {
                $tasks[] = [
                    'task_id' => null,
                    'task_code' => null,
                    'date' => $date->format('d-m-Y'),
                    'total' => 0
                ];                
            }

        }

        return response()->json(['success' => $tasks]);
    }

    public function logs(Request $request) {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:checklist_tasks,id'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error' => $errorString], 401);
        }

        $task = ChecklistTask::find($request->task_id);

        $isPointChecklist = Helper::isPointChecklist($task->form);

        $audits = [];

        foreach($task->audits()->where('event', 'updated')->latest()->get() as $logIndex => $log) {
            $deviceInfoEloquent = TaskDeviceInformation::where('eloquent', ChecklistTask::class)
                                ->where('eloquent_id', $task->id)
                                ->latest()
                                ->offset($logIndex)
                                ->limit(1)
                                ->first();

            $deviceIfno = [
                'device_model' => $deviceInfoEloquent->device_model ?? 'N/A',
                'network_speed' => $deviceInfoEloquent->network_speed ?? 'N/A',
                'device_version' => $deviceInfoEloquent->device_version ?? 'N/A'
            ];

            $tempLogArr = [
                'username' => isset($log->user()->first()->name) ? $log->user()->first()->name : 'User',
                'changes_date' => date('d F Y', strtotime($log->created_at)),
                'device_model' => $deviceIfno['device_model'],
                'network_speed' => $deviceIfno['network_speed'],
                'device_version' => $deviceIfno['device_version'],
                'timestamp' => date('d-m-Y H:i:s', strtotime($log->created_at))
            ];

            $arrForOldNew = [];
            
            foreach($log->old_values as $key => $value) {
                $arrForOldNew['title'] = ucwords(str_replace(' id', '', str_replace('_', ' ', $key)));
                $newVal = isset($log->new_values[$key]) ? $log->new_values[$key] : '';

                if ($key == 'status') {

                    if($value == 1) {
                        $arrForOldNew['old'] = 'In-Progress';
                    } else if ($value == 2) {
                        $arrForOldNew['old'] = 'Pending Verification';
                    } else if ($value == 3) {
                        $arrForOldNew['old'] = 'Verified';
                    } else {
                        $arrForOldNew['old'] = 'Pending';
                    }

                    if($newVal == 1) {
                        $arrForOldNew['new'] = 'In-Progress';
                    } else if ($newVal == 2) {
                        $arrForOldNew['new'] = 'Pending Verification';
                    } else if ($newVal == 3) {
                        $arrForOldNew['new'] = 'Verified';
                    } else {
                        $arrForOldNew['new'] = 'Pending';
                    }

                } else if ($key == 'data') {

                    // Old Data

                    if (is_string($value)) {
                        $data = json_decode($value, true);
                    } else if (is_array($value) || is_object($value)) {
                        $data = $value;
                    } else {
                        $data = [];
                    }

                    $groupedData = [];
                    foreach ($data as $item) {
                        if (is_object($item)) {
                            $groupedData[$item->className][] = $item;
                        } else {
                            $groupedData[$item['className']][] = $item;
                        }
                    }

                    $groupedData = json_decode(json_encode($groupedData));
                    

                    // NEW
                    if (is_string($newVal)) {
                        $data = json_decode($newVal, true);
                    } else if (is_array($newVal) || is_object($newVal)) {
                        $data = $newVal;
                    } else {
                        $data = [];
                    }

                    $groupedData2 = [];
                    foreach ($data as $item) {
                        if (is_object($item)) {
                            $groupedData2[$item->className][] = $item;
                        } else {
                            $groupedData2[$item['className']][] = $item;
                        }
                    }

                    $groupedData2 = json_decode(json_encode($groupedData2));
                    // NEW

                    foreach ($groupedData2 as $className => $fields) {
                        $arrForOldNew['data'][$className]['key'] = isset($fields[0]->label) ? $fields[0]->label : 'N/A';
                        foreach ($fields as $fk => $field) {

                            if($field->isFile) {
                                if(is_array($field->value)) {
                                    $tmparr = [];
                                    $tmparr2 = [];
                                    foreach ($field->value as $tk1 => $thisImg) {
                                        $tmparr[] = asset("storage/workflow-task-uploads/" . str_replace('assets/app/public/workflow-task-uploads/', '', $thisImg));
                                        if (isset($groupedData->{$className}[$fk][$tk1]) && $groupedData->{$className}[$fk][$tk1] != $thisImg) {
                                            $tmparr2[] = asset("storage/workflow-task-uploads/" . str_replace('assets/app/public/workflow-task-uploads/', '', $groupedData->{$className}[$fk][$tk1]));
                                        }
                                    }

                                    if ((count($tmparr) === count($tmparr2) && empty(array_diff($tmparr, $tmparr2)) && empty(array_diff($tmparr2, $tmparr)))) {
                                        $arrForOldNew['data'][$className]['new_value'] = $tmparr;
                                        $arrForOldNew['data'][$className]['old_value'] = $tmparr2;
                                    } else {
                                        if (isset($arrForOldNew['data'][$className]['key'])) {
                                            unset($arrForOldNew['data'][$className]['key']);
                                        }
                                    }
                                } else {
                                    if ((isset($groupedData->{$className}[$fk]->value))) {
                                            if ($groupedData->{$className}[$fk]->value != $field->value) {
                                                $arrForOldNew['data'][$className]['new_value'][] = ['key' => $field->label, 'value' => asset("storage/workflow-task-uploads/" . str_replace('assets/app/public/workflow-task-uploads/', '', $field->value))];
                                                $arrForOldNew['data'][$className]['old_value'][] = ['key' => $field->label, 'value' => asset("storage/workflow-task-uploads/" . str_replace('assets/app/public/workflow-task-uploads/', '', $groupedData->{$className}[$fk]->value))];
                                            } else {
                                                if (isset($arrForOldNew['data'][$className]['key'])) {
                                                    unset($arrForOldNew['data'][$className]['key']);
                                                }
                                            }
                                    } else {
                                        $arrForOldNew['data'][$className]['new_value'][] = ['key' => $field->label, 'value' => asset("storage/workflow-task-uploads/" . str_replace('assets/app/public/workflow-task-uploads/', '', $field->value))];
                                        $arrForOldNew['data'][$className]['old_value'][] = ['key' => $field->label, 'value' => null];
                                    }
                                }
                            } else {
                                if(property_exists($field, 'value_label')) {
                                    if($isPointChecklist) {
                                        if(is_array($field->value_label)) {
                                            if (isset($groupedData->{$className}[$fk]->value_label) && (count($groupedData->{$className}[$fk]->value_label) === count($field->value_label) && empty(array_diff($groupedData->{$className}[$fk]->value_label, $field->value_label)) && empty(array_diff($field->value_label, $groupedData->{$className}[$fk]->value_label)))) {
                                                $arrForOldNew['data'][$className]['new_value'][] = ['key' => $field->label, 'value' => implode(',', $field->value_label)];
                                                $arrForOldNew['data'][$className]['old_value'][] = ['key' => $field->label, 'value' => implode(',', $groupedData->{$className}[$fk]->value_label)];
                                            } else {
                                                if (isset($arrForOldNew['data'][$className]['key'])) {
                                                    unset($arrForOldNew['data'][$className]['key']);
                                                }
                                            }
                                        } else {
                                            if ((isset($groupedData->{$className}[$fk]->value))) {
                                                if ($groupedData->{$className}[$fk]->value != $field->value) {
                                                    $arrForOldNew['data'][$className]['new_value'][] = ['key' => $field->label, 'value' => $field->value_label . ' ' . $field->value];
                                                    $arrForOldNew['data'][$className]['old_value'][] = ['key' => $field->label, 'value' => $groupedData->{$className}[$fk]->value_label . ' ' . $groupedData->{$className}[$fk]->value];
                                                } else {
                                                    if (isset($arrForOldNew['data'][$className]['key'])) {
                                                        unset($arrForOldNew['data'][$className]['key']);
                                                    }
                                                }
                                            } else {
                                                $arrForOldNew['data'][$className]['new_value'][] = ['key' => $field->label, 'value' => $field->value_label . ' ' . $field->value];
                                                $arrForOldNew['data'][$className]['old_value'][] = ['key' => $field->label, 'value' => null];
                                            }
                                        }
                                    } else {
                                        if(is_array($field->value_label)) {
                                            if ((isset($groupedData->{$className}[$fk]->value_label))) {
                                                if ($groupedData->{$className}[$fk]->value_label != $field->value_label) {
                                                    $arrForOldNew['data'][$className]['old_value'][] = ['key' => $field->label, 'value' => implode(',', $groupedData->{$className}[$fk]->value_label)];
                                                    $arrForOldNew['data'][$className]['new_value'][] = ['key' => $field->label, 'value' => implode(',', $field->value_label)];
                                                } else {
                                                    if (isset($arrForOldNew['data'][$className]['key'])) {
                                                        unset($arrForOldNew['data'][$className]['key']);
                                                    }   
                                                }
                                            } else {
                                                $arrForOldNew['data'][$className]['old_value'][] = ['key' => $field->label, 'value' => null];
                                                $arrForOldNew['data'][$className]['new_value'][] = ['key' => $field->label, 'value' => implode(',', $field->value_label)];
                                            }
                                        } else {
                                            if ((isset($groupedData->{$className}[$fk]->value_label))) {
                                                if ($groupedData->{$className}[$fk]->value_label != $field->value_label) {
                                                    $arrForOldNew['data'][$className]['old_value'][] = ['key' => $field->label, 'value' => implode(',', $groupedData->{$className}[$fk]->value_label)];
                                                    $arrForOldNew['data'][$className]['new_value'][] = ['key' => $field->label, 'value' => $field->value_label];
                                                } else {
                                                if (isset($arrForOldNew['data'][$className]['key'])) {
                                                    unset($arrForOldNew['data'][$className]['key']);
                                                }
                                                }
                                            } else {
                                                if (isset($arrForOldNew['data'][$className]['key'])) {
                                                    unset($arrForOldNew['data'][$className]['key']);
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    if(is_array($field->value)) {
                                        if ((isset($groupedData->{$className}[$fk]->value))) {
                                            if ($groupedData->{$className}[$fk]->value != $field->value) {
                                                $arrForOldNew['data'][$className]['old_value'][] = ['key' => $field->label, 'value' => implode(',', $groupedData->{$className}[$fk]->value)];
                                                $arrForOldNew['data'][$className]['new_value'][] = ['key' => $field->label, 'value' => implode(',', $field->value)];
                                            } else {
                                                if (isset($arrForOldNew['data'][$className]['key'])) {
                                                    unset($arrForOldNew['data'][$className]['key']);
                                                }
                                            }
                                        } else {
                                            $arrForOldNew['data'][$className]['old_value'][] = ['key' => $field->label, 'label' => null];
                                            $arrForOldNew['data'][$className]['new_value'][] = ['key' => $field->label, 'label' => implode(',', $field->value)];
                                        }
                                    } else {
                                        if ((isset($groupedData->{$className}[$fk]->value))) {
                                            if ($groupedData->{$className}[$fk]->value != $field->value) {
                                                $arrForOldNew['data'][$className]['old_value'][] = ['key' => $field->label, 'label' => $groupedData->{$className}[$fk]->value];
                                                $arrForOldNew['data'][$className]['new_value'][] = ['key' => $field->label, 'label' => $field->value];
                                            } else {
                                                if (isset($arrForOldNew['data'][$className]['key'])) {
                                                    unset($arrForOldNew['data'][$className]['key']);
                                                }
                                            }
                                        } else {
                                            $arrForOldNew['data'][$className]['old_value'][] = ['key' => $field->label, 'label' => null];
                                            $arrForOldNew['data'][$className]['new_value'][] = ['key' => $field->label, 'label' => $field->value];
                                        }
                                    }
                                }
                            }

                        }
                    }

                    if (array_key_exists('data', $arrForOldNew) && !is_string($arrForOldNew['data']) && !empty($arrForOldNew['data'])) {
                        $arrForOldNew['data'] = array_values($arrForOldNew['data']);
                        $arrForOldNew['data'] = array_filter($arrForOldNew['data']);
                        $arrForOldNew['data'] = array_values($arrForOldNew['data']);
                    } else {
                        $arrForOldNew['data'] = [];                        
                    }

                    // Old Data

                } else {

                        $arrForOldNew['old'] = $value;
                        $arrForOldNew['new'] = $newVal;

                }

                $tempLogArr['changes'][] = $arrForOldNew;
                $arrForOldNew = [];
            }

            $audits[] = $tempLogArr;
        }

        return response()->json(['success' => $audits]);
    }

    public function topics(Request $request)
    {
        $query = Topic::query()->where('status', 1)->whereNull('deleted_at');
        $allTopics = $query->get();

        $parentId = $request->parent_id ?? 0;
        $type = $request->type;

        if ($type === 'tree') {
            $tree = $this->buildTree($allTopics, $parentId);
            return response()->json(['success' => $tree->values()]);
        }

        if ($type === 'list' && $request->has('parent_id')) {
            $descendants = $this->getAllDescendantsFlat($allTopics, $parentId);
            return response()->json(['success' => $descendants->values()]);
        }

        if ($type === 'list') {
            return response()->json(['success' => $allTopics->values()]);
        }

        return response()->json(['success' => $allTopics->values()]);
    }

    private function buildTree($topics, $parentId = 0)
    {
        $branch = collect();

        foreach ($topics as $topic) {
            if ($topic->parent_id == $parentId) {
                $children = $this->buildTree($topics, $topic->id);
                if ($children->isNotEmpty()) {
                    $topic->children = $children;
                }
                $branch->push($topic);
            }
        }

        return $branch;
    }

    private function getAllDescendantsFlat($topics, $parentId)
    {
        $flat = collect();

        foreach ($topics as $topic) {
            if ($topic->parent_id == $parentId) {
                $flat->push($topic);
                $children = $this->getAllDescendantsFlat($topics, $topic->id);
                $flat = $flat->merge($children);
            }
        }

        return $flat;
    }

    public function tags(Request $request) {
        return response()->json(['success' => Tag::get()]);
    }

    public function content(Request $request) {
        $page = $request->page > -1 ? $request->page : 0;
        $perPage = $request->record_per_page > 0 ? $request->record_per_page : 5;
        $skip = $page * $perPage;

        $currentUser = auth()->user()->id;
        $currentUserRoles = auth()->user()->roles()->pluck('id')->toArray();

        $contents = Content::where('status', true);

        if ($request->has('content_id')) {
            $contents = $contents->where('id', $request->content_id);
        }

        if ($request->has('topics') && is_array($request->topics) && !empty($request->topics)) {
            $contents = $contents->whereIn('topic_id', $request->topics);
        }

        if ($request->has('tags') && is_array($request->tags) && !empty($request->tags)) {
            $contents = $contents->whereHas('tags', function ($builder) {
                $builder->whereIn('tag_id', request('tags'));
            });
        }

        $contentCount = $contents->clone()->count();

        $contents = $contents
        ->skip($skip)
        ->take($perPage)
        ->get()
        ->map(function ($row) use ($currentUserRoles, $currentUser) {

            if (isset($row->permission->id)) {
                $permissions = $row->permission->permission_matrix;
                if (!empty($permissions)) {
                    $allRoles = $permissions->roles;
                    $allUsers = $permissions->users;
                    $canAccess = false;

                    if (!empty($currentUserRoles)) {
                        if ($permissions->type == 1) {

                            if (!empty($allRoles)) {
                                foreach ($currentUserRoles as $currentUserRoleRow) {
                                    if (in_array($currentUserRoleRow, $allRoles)) {
                                        $canAccess = true;
                                        break;
                                    }
                                }
                            }
                        } else if ($permissions->type == 2) {
                            if (!empty($allRoles) &&  !empty($allUsers)) {
                                foreach ($currentUserRoles as $currentUserRoleRow) {
                                    if ($canAccess) {
                                        break;
                                    }

                                    if (in_array($currentUserRoleRow, $allRoles)) {
                                        foreach ($allUsers as $thisUser) {
                                            if ($thisUser == $currentUser) {
                                                $canAccess = true;
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        } else if ($permissions->type == 3) {
                            $shouldReject = false;

                            if (!empty($allRoles) &&  !empty($allUsers)) {
                                foreach ($currentUserRoles as $currentUserRoleRow) {
                                    if ($shouldReject) {
                                        break;
                                    }

                                    if (in_array($currentUserRoleRow, $allRoles)) {
                                        foreach ($allUsers as $thisUser) {
                                            if ($thisUser == $currentUser) {
                                                $shouldReject = true;
                                                break;
                                            }
                                        }
                                    }
                                }
                            }

                            if ($shouldReject === false) {
                                $canAccess = true;
                            }
                        }
                    }

                    if ($canAccess) {
                        return [
                            'id' => $row->id,
                            'topic' => $row->topic,
                            'title' => $row->title,
                            'description' => $row->description,
                            'tags' => $row->tags->map(function ($el) {
                                return [
                                    'id' => $el->id,
                                    'title' => $el->tag->title ?? ''
                                ];
                            }),
                            'slug' => $row->slug,
                            'attachments' => $row->attachments->map(function ($el) {
                                return [
                                    'id' => $el->id,
                                    'type' => $el->type,
                                    'path' => asset('storage/content_attachments/' . $el->path),
                                    'description' => $el->description,
                                    'order' => 1,
                                    'viewed_seconds' => $el->analytics->watching_time ?? 0,
                                    'allover_wathching_time' => $el->analytics->allover_wathching_time ?? 0
                                ];
                            })
                        ];
                    }
                }
            }
        });
        
        $contents = $contents->filter()->values()->toArray();

        return response()->json(['success' => $contents, 'total_records' => $contentCount, 'page' => intval($page), 'record_per_page' => $perPage], 200); 
    }

    public function viewCount(Request $request) {
        $validator = Validator::make($request->all(), [
            'content_video_id' => 'required|exists:content_attachments,id',
            'watching_time' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error' => $errorString], 401);
        }

        $anal = ContentAnalytic::where('user_id', auth()->user()->id)
        ->where('content_attachment_id', $request->content_video_id)
        ->first();

        if ($anal) {

            $anal->update([
                'total_seconds' => $request->total_seconds,
                'watching_time' => $request->watching_time,
                'allover_wathching_time' => $anal->allover_wathching_time + $request->watching_time,
            ]);

        } else {
            ContentAnalytic::create([
                'user_id' => auth()->user()->id,
                'total_seconds' => $request->total_seconds,
                'content_attachment_id' => $request->content_video_id,
                'watching_time' => $request->watching_time,
                'allover_wathching_time' => $request->watching_time,
            ]);
        }

        return response()->json(['success' => 'Watching time updated successfully']);
    }

    public function addTicket(Request $request) {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:checklist_tasks,id',
            'field_id' => 'required'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['error' => $errorString], 401);
        }

        $ticket = new Ticket();
        $deptUser = DepartmentUser::with(['user'])->where('department_id', $request->department_id)->get();

        if ($deptUser->isEmpty()) {
            return response()->json(['error' => 'Department has no users']);
        }

        $tick_it = Ticket::latest()->first();
        if(empty($tick_it)) {
            $ticket->ticket_number = "TW-1001";
        } else {
            if(empty($tick_it->ticket_number)) {
                $ticket->ticket_number = "TW-1001";
            } else {
                $tix = explode('-', $tick_it->ticket_number);
                $number = $tix[1];
                $main_number = (int) substr($number, 0, -3);
                $plus_number = (int) substr($number, -3);
                $catch_number = sprintf('%03d', $plus_number + 1);
                if($plus_number >= 999) {
                    $catch_number = "001";
                    $main_number = $main_number + 1;
                }
                $str = "TW-".$main_number.$catch_number;
                $ticket->ticket_number = $str;
            }
        }

        $ticket->task_id = $request->task_id;
        $ticket->subject = $request->subject;
        $ticket->department_id = $request->department_id;
        $ticket->field_id = $request->field_id;
        $ticket->priority_id = \App\Models\Priority::first()->id;
        $ticket->status_id = \App\Models\Status::first()->id;
        $ticket->setPurifiedContent($request->get('content'));
        $ticket->user_id = auth()->user()->id;
        $ticket->save();

        $allSavedFiles = [];

        if ($request->hasFile('attachments')) {
            if (!file_exists(storage_path('app/public/ticket-uploads'))) {
                mkdir(storage_path('app/public/ticket-uploads'), 0777, true);
            }

            foreach ($request->file('attachments') as $file) {
                $fileName = 'TU-' . date('YmdHis') . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(storage_path('app/public/ticket-uploads'), $fileName);

                if (is_file(storage_path("app/public/ticket-uploads/{$fileName}"))) {
                    $allSavedFiles[] = \App\Models\TicketAttachment::create([
                        'ticket_id' => $ticket->id,
                        'file' => $fileName
                    ])->id;
                }
            }
        }

        \App\Models\TicketHistory::updateOrCreate([
            "ticket_id" => $ticket->id,
            "description" => "Ticket has been created by ".auth()->user()->name,
            "user_id" => auth()->user()->id
        ], [
            "ticket_id" => $ticket->id,
            "description" => "Ticket has been created by ".auth()->user()->name,
            "user_id" => auth()->user()->id,
            "created_at" => Carbon::now(),
            "updated_at" => Carbon::now()
        ]);

        foreach ($deptUser as $agentId) {
            \App\Models\TicketMember::create([
                'ticket_id' => $ticket->id,
                'user_id' => $agentId->user_id
            ]);

            \App\Models\TicketHistory::updateOrCreate([
                "ticket_id" => $ticket->id,
                "description" => "Ticket has been assigned to ".$agentId->user->name ?? '',
                "user_id" => auth()->user()->id
            ], [
                "ticket_id" => $ticket->id,
                "description" => "Ticket has been assigned to ".$agentId->user->name ?? '',
                "user_id" => auth()->user()->id,
                "created_at" => Carbon::now(),
                "updated_at" => Carbon::now()
            ]);
        }

        if (!empty($allSavedFiles)) {
            \App\Models\TicketHistory::create([
                "ticket_id" => $ticket->id,
                "description" => "Attachments has been added with ticket generation",
                "type" => 1,
                "model" => \App\Models\TicketAttachment::class,
                "model_id" => $allSavedFiles[0],
                "user_id" => auth()->user()->id,
                "created_at" => Carbon::now(),
                "updated_at" => Carbon::now()
            ]);
        }

        // Mail send for all user
        Helper::ticket_mail_send($ticket->id,'Add');

        return response()->json(['success' => 'Ticked created successfully']);
    }
}
