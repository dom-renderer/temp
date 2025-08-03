<?php

namespace App\Http\Controllers;

use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationTemplateController extends Controller
{
    protected $title = 'Notification Templates';
    protected $view = 'notification-templates.';

    public function __construct()
    {
        $this->middleware('permission:notification-templates.index')->only(['index', 'ajax']);
        $this->middleware('permission:notification-templates.create')->only(['create']);
        $this->middleware('permission:notification-templates.store')->only(['store']);
        $this->middleware('permission:notification-templates.edit')->only(['edit']);
        $this->middleware('permission:notification-templates.update')->only(['update']);
        $this->middleware('permission:notification-templates.show')->only(['show']);
        $this->middleware('permission:notification-templates.destroy')->only(['destroy']);
    }

    public function index()
    {
        if (request()->ajax()) {
            return $this->ajax();
        }
        $title = $this->title;
        $subTitle = 'Manage notification templates here';
        return view($this->view . 'index', compact('title', 'subTitle'));
    }

    public function ajax()
    {
        $query = NotificationTemplate::query();

        if (request('filter_status') !== null && request('filter_status') !== '') {
            $query->where('status', request('filter_status'));
        }

        return datatables()
            ->eloquent($query)
            ->editColumn('type', function ($row) {
                return $row->type_display;
            })
            ->editColumn('status', function ($row) {
                $statuses = ['ACTIVE', 'INACTIVE'];
                $html = '<select class="form-select change-status" data-old="' . $row->status . '" data-id="' . $row->id . '" data-url="' . route('notification-templates.change-status', $row->id) . '">';
                foreach ($statuses as $status) {
                    $selected = $row->status === $status ? 'selected' : '';
                    $html .= "<option value='{$status}' {$selected}>{$status}</option>";
                }
                $html .= '</select>';
                return $html;
            })
            ->editColumn('body', function ($row) {
                return strip_tags(substr($row->body, 0, 100)) . (strlen($row->body) > 100 ? '...' : '');
            })
            ->addColumn('action', function ($row) {
                $html = '';

                if (auth()->user()->can('notification-templates.edit')) {
                    $html .= '<a href="' . route('notification-templates.edit', encrypt($row->id)) . '" class="btn btn-sm btn-primary"> <i class="fa fa-edit"> </i> </a>&nbsp;';
                }
                if (auth()->user()->can('notification-templates.destroy')) {
                    $html .= '<button type="button" class="btn btn-sm btn-danger" id="deleteRow" data-row-route="' . route('notification-templates.destroy', $row->id) . '"> <i class="fa fa-trash"> </i> </button>&nbsp;';
                }
                if (auth()->user()->can('notification-templates.show')) {
                    $html .= '<a href="' . route('notification-templates.show', encrypt($row->id)) . '" class="btn btn-sm btn-secondary"> <i class="fa fa-eye"> </i> </a>&nbsp;';
                }

                return $html;
            })
            ->rawColumns(['status', 'action'])
            ->addIndexColumn()
            ->toJson();
    }

    public function create()
    {
        $title = $this->title;
        $subTitle = 'Add New Notification Template';
        $notificationTypes = ['email' => 'Email', 'push-notification' => 'Push Notification', 'system' => 'System'];
        $availableVariables = [
            '{user_name}',
            '{user_email}',
            '{job_title}',
            '{job_code}',
            '{job_status}',
            '{customer_name}',
            '{customer_email}',
            '{technician_name}',
            '{department_name}',
            '{expertise_name}',
            '{company_name}',
            '{current_date}',
            '{current_time}'
        ];
        return view($this->view . 'create', compact('title', 'subTitle', 'notificationTypes', 'availableVariables'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|array|min:1',
            'type.*' => 'in:email,push-notification,system',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);

        DB::beginTransaction();

        try {
            NotificationTemplate::create([
                'type' => $request->type,
                'title' => $request->title,
                'body' => $request->body,
                'status' => $request->status,
            ]);

            DB::commit();
            return redirect()->route('notification-templates.index')->with('success', 'Notification template created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('notification-templates.index')->with('error', 'Something Went Wrong: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $template = NotificationTemplate::findOrFail(decrypt($id));
            
            $title = $this->title;
            $subTitle = 'Notification Template Details';
            
            return view($this->view . 'show', compact('template', 'title', 'subTitle'));
        } catch (\Exception $e) {
            return redirect()->route('notification-templates.index')->with('error', 'Notification template not found.');
        }
    }

    public function edit($id)
    {
        try {
            $template = NotificationTemplate::findOrFail(decrypt($id));
            
            $title = $this->title;
            $subTitle = 'Edit Notification Template';
            $notificationTypes = ['email' => 'Email', 'push-notification' => 'Push Notification', 'system' => 'System'];
            $availableVariables = [
                '{user_name}',
                '{user_email}',
                '{job_title}',
                '{job_code}',
                '{job_status}',
                '{customer_name}',
                '{customer_email}',
                '{technician_name}',
                '{department_name}',
                '{expertise_name}',
                '{company_name}',
                '{current_date}',
                '{current_time}'
            ];
            return view($this->view . 'edit', compact('template', 'title', 'subTitle', 'notificationTypes', 'availableVariables'));
        } catch (\Exception $e) {
            return redirect()->route('notification-templates.index')->with('error', 'Notification template not found.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $template = NotificationTemplate::findOrFail(decrypt($id));

            $request->validate([
                'type' => 'required|array|min:1',
                'type.*' => 'in:email,push-notification,system',
                'title' => 'required|string|max:255',
                'body' => 'required|string',
                'status' => 'required|in:ACTIVE,INACTIVE',
            ]);

            DB::beginTransaction();

            $template->update([
                'type' => $request->type,
                'title' => $request->title,
                'body' => $request->body,
                'status' => $request->status,
            ]);

            DB::commit();
            return redirect()->route('notification-templates.index')->with('success', 'Notification template updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('notification-templates.index')->with('error', 'Something Went Wrong: ' . $e->getMessage());
        }
    }

    public function destroy(NotificationTemplate $notificationTemplate)
    {
        try {
            $notificationTemplate->delete();
            return response()->json(['success' => 'Notification template deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong!']);
        }
    }

    public function changeStatus(Request $request, NotificationTemplate $notificationTemplate)
    {
        $request->validate([
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);

        try {
            $notificationTemplate->update(['status' => $request->status]);
            return response()->json(['status' => true, 'message' => 'Status updated successfully.']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Something went wrong!']);
        }
    }
}
