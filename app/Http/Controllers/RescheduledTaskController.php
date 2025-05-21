<?php

namespace App\Http\Controllers;

use App\Models\RescheduledTask;
use App\Models\ChecklistTask;
use Illuminate\Http\Request;
use App\Helpers\Helper;

class RescheduledTaskController extends Controller
{
    public function index(Request $request) {
        if ($request->ajax()) {
            $currentUser = auth()->user()->id;
            $thisUserRoles = auth()->user()->roles()->pluck('id')->toArray();

            $tasks = RescheduledTask::query()
            ->when(!in_array(Helper::$roles['admin'], $thisUserRoles), function ($builder) use ($currentUser) {
                $builder->where(function ($innerBuilder) use ($currentUser) {
                    $innerBuilder->whereHas('task.parent.parent', function ($innerBuilder2) use ($currentUser) {
                        $innerBuilder2->where('checker_user_id', $currentUser);
                    })->orWhereHas('task.parent', function ($innerBuilder2) use ($currentUser) {
                        $innerBuilder2->where('user_id', $currentUser);
                    });
                });
            })
            ->when(!empty($request->locs), function ($builder) {
                return $builder->whereHas('task.parent', function ($innerBuilder) {
                    $innerBuilder->whereIn('store_id', request('locs'));
                });
            })
            ->when(!empty($request->user), function ($builder) {
                return $builder->whereHas('task.parent', function ($innerBuilder) {
                    $innerBuilder->whereIn('user_id', request('user'));
                });
            })
            ->when(!empty($request->checker), function ($builder) {
                return $builder->whereHas('task.parent.parent', function ($innerBuilder) {
                    $innerBuilder->whereIn('checker_user_id', request('checker'));
                });
            })
            ->when(!empty($request->checklist), function ($builder) {
                return $builder->whereHas('task.parent.parent', function ($innerBuilder) {
                    return $innerBuilder->whereIn('checklist_id', request('checklist'));
                });
            })
            ->when(!empty($request->from), function ($builder) {
                return $builder->whereHas('task', function ($x) {
                    $x->where(\DB::raw("DATE_FORMAT(date, '%Y-%m-%d')"), '>=', date('Y-m-d', strtotime(request('from'))));
                });
            })
            ->when(!empty($request->to), function ($builder) {
                return $builder->whereHas('task', function ($x) {
                    $x->where(\DB::raw("DATE_FORMAT(date, '%Y-%m-%d')"), '<=', date('Y-m-d', strtotime(request('to'))));
                });
            })
            ->when($request->status === '0' || in_array($request->status, range(1, 2)), function ($builder) {
                return $builder->whereHas('task', function ($x) {
                    $x->where('status',request('status'));
                });
            })
            ->orderBy('id', 'DESC');

            return datatables()
            ->eloquent($tasks)
            ->addColumn('action', function ($row) {
                $action = '';

                if ($row->status === 0) {
                    if ($row->task->parent->parent->checker_user_id == auth()->user()->id || in_array(Helper::$roles['admin'], auth()->user()->roles()->pluck('id')->toArray())) {
                        $action .= '
                        <a data-href="'.route("submit-reschedule-response", encrypt($row->id)).'" class="btn btn-success btn-sm me-2 approve-task"> Approve </a>
                        <a data-href="'.route("submit-reschedule-response", encrypt($row->id)).'" class="btn btn-danger btn-sm me-2 disapprove-task"> Reject </a>';
                    }
                } else {
                    if ($row->status == 1) {
                        $action .= "Approved";
                    } else {
                        $action .= "Rejected";
                    }
                }

                return $action;
            })
            ->addColumn('taskcode', function ($row) {
                return $row->task->code ?? '';
            })
            ->addColumn('taskstorename', function ($row) {
                return $row->task->parent->actstore->name ?? '';
            })
            ->addColumn('taskchecklistname', function ($row) {
                return $row->task->parent->parent->checklist->name ?? '';
            })
            ->addColumn('actual_date', function ($row) {
                return date('d-m-Y H:i', strtotime($row->task->date ?? ''));
            })
            ->addColumn('res_date', function ($row) {
                return date('d-m-Y H:i', strtotime($row->date ?? ''));
            })
            ->editColumn('remarks', function ($row) {
                return '<button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#remark-viwer" data-remarks="' . $row->remarks . '"> View </button>';
            })
            ->rawColumns(['action', 'remarks'])
            ->toJson();
        }

        $page_title = 'Rescheduled Tasks';
        $page_description = 'Manage rescheduled tasks here';
        return view('reschedules.index',compact('page_title', 'page_description'));
    }

    public function submitRescheduleResponse(Request $request, $id) {
        $id = decrypt($id);
        
        RescheduledTask::where('id' , $id)->update([
            'status' => $request->status
        ]);

        $task = RescheduledTask::where('id' , $id)->first();
                
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

        return response()->json(['status' => true, 'message' => 'Status is updated successfully']);
    }
}
