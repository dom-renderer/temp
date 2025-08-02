<?php

namespace App\Http\Controllers;

use App\Models\JobRescheduleRequest;
use Illuminate\Support\Facades\DB;
use App\Models\JobTechnician;
use App\Models\JobExpertise;
use Illuminate\Http\Request;
use App\Models\JobMaterial;
use App\Helpers\Helper;
use App\Models\User;
use App\Models\Job;

class JobController extends Controller
{
    protected $title = 'Jobs';
    protected $view = 'jobs.';

    public function __construct()
    {
        $this->middleware('permission:jobs.index')->only(['index', 'ajax']);
        $this->middleware('permission:jobs.create')->only(['create']);
        $this->middleware('permission:jobs.store')->only(['store']);
        $this->middleware('permission:jobs.edit')->only(['edit']);
        $this->middleware('permission:jobs.update')->only(['update']);
        $this->middleware('permission:jobs.show')->only(['show']);
        $this->middleware('permission:jobs.destroy')->only(['destroy']);
        $this->middleware('permission:jobs.reschedule')->only(['reschedule']);
    }

    public function index()
    {
        if (request()->ajax()) {
            return $this->ajax();
        }
        $title = $this->title;
        $subTitle = 'Manage jobs here';
        return view($this->view . 'index', compact('title', 'subTitle'));
    }

    public function ajax()
    {
        $query = Job::query();

        if (request('filter_status') !== null && request('filter_status') !== '') {
            $query->where('status', request('filter_status'));
        }

        return datatables()
            ->eloquent($query)
            ->editColumn('status', function ($row) {
                $statuses = ['PENDING', 'INPROGRESS', 'COMPLETED', 'CANCELLED'];
                $html = '<select class="form-select change-status" data-old="' . $row->status . '" data-id="' . $row->id . '" data-url="' . route('jobs.change-status', $row->id) . '">';
                foreach ($statuses as $status) {
                    $selected = $row->status === $status ? 'selected' : '';
                    $html .= "<option value='{$status}' {$selected}>{$status}</option>";
                }
                $html .= '</select>';
                return $html;
            })            
            ->addColumn('action', function ($row) {
                $html = '';

                if (auth()->user()->can('jobs.edit')) {
                    if (in_array($row->status, ['PENDING', 'INPROGRESS'])) {
                        $html .= '<a href="' . route('jobs.edit', encrypt($row->id)) . '" class="btn btn-sm btn-primary"> <i class="fa fa-edit"> </i> </a>&nbsp;';
                    }
                }
                if (auth()->user()->can('jobs.destroy')) {
                    $html .= '<button type="button" class="btn btn-sm btn-danger" id="deleteRow" data-row-route="' . route('jobs.destroy', $row->id) . '"> <i class="fa fa-trash"> </i> </button>&nbsp;';
                }
                if (auth()->user()->can('jobs.show')) {
                    $html .= '<a href="' . route('jobs.show', encrypt($row->id)) . '" class="btn btn-sm btn-secondary"> <i class="fa fa-eye"> </i> </a>&nbsp;';
                }

                if (auth()->user()->can('jobs.reschedule')) {
                    if (in_array($row->status, ['PENDING', 'INPROGRESS'])) {
                        $html .= '<button type="button" class="btn btn-sm btn-warning reschedule-btn" 
                                    data-id="' . $row->id . '" 
                                    data-url="' . route('jobs.reschedule', $row->id) . '">
                                    <i class="fa fa-calendar"></i> Reschedule
                                </button>&nbsp;';
                    }
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
        $subTitle = 'Add New Job';
        return view($this->view . 'create', compact('title', 'subTitle'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer' => 'required|exists:users,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_alternate_phone_number' => 'required|string|max:20',
            'customer_billing_name' => 'required|string|max:255',
            'customer_address_line_1' => 'required|string',
            'opening_date' => 'required|date_format:d-m-Y',
            'visiting_date' => 'required|date_format:d-m-Y',
            'technicians' => 'required|array|min:1',
            'technicians.*' => 'exists:users,id',
            'description' => 'required|string',
            'summary' => 'nullable|string',
            'requires_deposit' => 'boolean',
            'deposit_type' => 'required_if:requires_deposit,1|in:FIX,PERCENT',
            'deposit_amount' => 'required_if:requires_deposit,1|min:0|max:100',
            'material' => 'nullable|array',
            'material.*.product' => 'required_with:material|exists:products,id',
            'material.*.quantity' => 'required_with:material|numeric|min:1',
            'material.*.price' => 'required_with:material|numeric|min:0',
            'material.*.amount' => 'required_with:material|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $jobData = [
                'code' => Helper::jobCode(),
                'customer_id' => $request->customer,
                'assigner_id' => auth()->user()->id,
                'title' => $request->title,
                
                'contact_name' => $request->customer_name,
                'contact_dial_code' => $request->customer_alternate_dial_code,
                'contact_phone_number' => $request->customer_alternate_phone_number,
                'billing_name' => $request->customer_billing_name,
                'email' => $request->customer_email,
                'address_line_1' => $request->customer_address_line_1,
                'address_line_2' => $request->customer_address_line_2,
                'latitude' => $request->customer_latitude,
                'longitude' => $request->customer_longitude,
                'location_url' => $request->customer_location_url,
                
                'description' => $request->description,
                'summary' => $request->summary,
                'opening_date' => \Carbon\Carbon::createFromFormat('d-m-Y', $request->opening_date)->format('Y-m-d H:i:s'),
                'visiting_date' => \Carbon\Carbon::createFromFormat('d-m-Y', $request->visiting_date)->format('Y-m-d H:i:s'),
                'status' => 'PENDING',
                'requires_deposit' => $request->requires_deposit ?? false,
                'deposit_type' => $request->requires_deposit ? $request->deposit_type : 'FIX',
                'deposit_amount' => $request->requires_deposit ? $request->deposit_amount : 0,
            ];

            $job = Job::create($jobData);

            if ($request->has('technicians') && is_array($request->technicians)) {
                foreach ($request->technicians as $technicianId) {
                    JobTechnician::create([
                        'job_id' => $job->id,
                        'technician_id' => $technicianId
                    ]);
                }
            }

            if ($request->has('expertise') && is_array($request->expertise)) {
                foreach ($request->expertise as $expId) {
                    JobExpertise::create([
                        'job_id' => $job->id,
                        'expertise_id' => $expId
                    ]);
                }
            }

            if ($request->has('material') && is_array($request->material)) {
                foreach ($request->material as $material) {
                    if (!empty($material['product']) && !empty($material['quantity'])) {
                        JobMaterial::create([
                            'job_id' => $job->id,
                            'product_id' => $material['product'],
                            'description' => $material['description'] ?? null,
                            'quantity' => $material['quantity'],
                            'amount' => $material['price'],
                            'total' => $material['amount']
                        ]);
                    }
                }
            }

            DB::commit();
            return redirect()->route('jobs.index')->with('success', 'Job created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('jobs.index')->with('error', 'Something Went Wrong: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $job = Job::with(['customer', 'assigner', 'technicians', 'materials.product.category'])
                ->findOrFail(decrypt($id));
            
            $title = $this->title;
            $subTitle = 'Job Details';
            $times = Helper::calculateTotalTimePerEmployee($job->id);
            
            return view($this->view . 'show', compact('job', 'title', 'subTitle', 'times'));
        } catch (\Exception $e) {
            return redirect()->route('jobs.index')->with('error', 'Job not found.');
        }
    }

    public function edit($id)
    {
        try {
            $job = Job::with(['customer', 'technicians', 'materials.product.category'])->findOrFail(decrypt($id));
            
            if (!in_array($job->status, ['PENDING', 'INPROGRESS'])) {
                return redirect()->route('jobs.index')->with('error', 'Job cannot be edited in current status.');
            }
            
            $title = $this->title;
            $subTitle = 'Edit Job';
            return view($this->view . 'edit', compact('job', 'title', 'subTitle'));
        } catch (\Exception $e) {
            return redirect()->route('jobs.index')->with('error', 'Job not found.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $job = Job::findOrFail(decrypt($id));
            
            if (!in_array($job->status, ['PENDING', 'INPROGRESS'])) {
                return redirect()->route('jobs.index')->with('error', 'Job cannot be edited in current status.');
            }

            $request->validate([
                'customer_name' => 'required|string|max:255',
                'customer_email' => 'required|email|max:255',
                'customer_alternate_dial_code' => 'required|string|max:10',
                'customer_alternate_phone_number' => 'required|string|max:20',
                'customer_billing_name' => 'required|string|max:255',
                'customer_address_line_1' => 'required|string',
                'customer_address_line_2' => 'nullable|string',
                'customer_latitude' => 'nullable|numeric',
                'customer_longitude' => 'nullable|numeric',
                'customer_location_url' => 'nullable|string',
                'title' => 'required|string|max:255',
                'opening_date' => 'required|date_format:d-m-Y',
                'visiting_date' => 'required|date_format:d-m-Y',
                'technicians' => 'required|array|min:1',
                'technicians.*' => 'exists:users,id',
                'description' => 'required|string',
                'summary' => 'nullable|string',
                'requires_deposit' => 'boolean',
                'deposit_type' => 'required_if:requires_deposit,1|in:FIX,PERCENT',
                'deposit_amount' => 'required_if:requires_deposit,1|min:0|max:100',
                'material' => 'nullable|array',
                'material.*.product' => 'required_with:material|exists:products,id',
                'material.*.quantity' => 'required_with:material|numeric|min:1',
                'material.*.price' => 'required_with:material|numeric|min:0',
                'material.*.amount' => 'required_with:material|numeric|min:0',
            ]);

            DB::beginTransaction();

            $job->update([
                'title' => $request->title,
                'contact_name' => $request->customer_name,
                'contact_dial_code' => $request->customer_alternate_dial_code,
                'contact_phone_number' => $request->customer_alternate_phone_number,
                'billing_name' => $request->customer_billing_name,
                'email' => $request->customer_email,
                'address_line_1' => $request->customer_address_line_1,
                'address_line_2' => $request->customer_address_line_2,
                'latitude' => $request->customer_latitude,
                'longitude' => $request->customer_longitude,
                'location_url' => $request->customer_location_url,
                'description' => $request->description,
                'summary' => $request->summary,
                'opening_date' => \Carbon\Carbon::createFromFormat('d-m-Y', $request->opening_date)->format('Y-m-d H:i:s'),
                'visiting_date' => \Carbon\Carbon::createFromFormat('d-m-Y', $request->visiting_date)->format('Y-m-d H:i:s'),
                'requires_deposit' => $request->requires_deposit ?? false,
                'deposit_type' => $request->requires_deposit ? $request->deposit_type : 'FIX',
                'deposit_amount' => $request->requires_deposit ? $request->deposit_amount : 0,
            ]);

            $techs = [];
            if ($request->has('technicians') && is_array($request->technicians)) {
                foreach ($request->technicians as $technicianId) {
                    $techs[] = JobTechnician::updateOrCreate([
                        'job_id' => $job->id,
                        'technician_id' => $technicianId
                    ])->id;
                }
            }

            if (!empty($techs)) {
                JobTechnician::where('job_id', $job->id)->whereNotIn('id', $techs)->delete();
            } else {
                JobTechnician::where('job_id', $job->id)->delete();
            }

            $exps = [];
            if ($request->has('expertise') && is_array($request->expertise)) {
                foreach ($request->expertise as $expId) {
                    $exps[] = JobExpertise::updateOrCreate([
                        'job_id' => $job->id,
                        'expertise_id' => $expId
                    ])->id;
                }
            }

            if (!empty($exps)) {
                JobExpertise::where('job_id', $job->id)->whereNotIn('id', $exps)->delete();
            } else {
                JobExpertise::where('job_id', $job->id)->delete();
            }

            $existingMaterialIds = [];
            if ($request->has('material') && is_array($request->material)) {
                foreach ($request->material as $materialId => $material) {
                    if (!empty($material['product']) && !empty($material['quantity'])) {
                        if (is_numeric($materialId)) {
                            JobMaterial::where('id', $materialId)->where('job_id', $job->id)->update([
                                'product_id' => $material['product'],
                                'description' => $material['description'] ?? null,
                                'quantity' => $material['quantity'],
                                'amount' => $material['price'],
                                'total' => $material['amount']
                            ]);
                            $existingMaterialIds[] = $materialId;
                        } else {
                            JobMaterial::create([
                                'job_id' => $job->id,
                                'product_id' => $material['product'],
                                'description' => $material['description'] ?? null,
                                'quantity' => $material['quantity'],
                                'amount' => $material['price'],
                                'total' => $material['amount']
                            ]);
                        }
                    }
                }
            }
            
            JobMaterial::where('job_id', $job->id)->whereNotIn('id', $existingMaterialIds)->delete();

            DB::commit();
            return redirect()->route('jobs.index')->with('success', 'Job updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('jobs.index')->with('error', 'Something Went Wrong: ' . $e->getMessage());
        }
    }

    public function reschedule(Request $request, Job $job)
    {
        $request->validate([
            'reschedule_date' => 'required|date',
            'reason' => 'required|string|max:255'
        ]);

        DB::beginTransaction();

        try {
            JobRescheduleRequest::create([
                'job_id' => $job->id,
                'rescheduled_at' => date('Y-m-d H:i:s', strtotime($request->reschedule_date)),
                'reschedule_reason' => $request->reason
            ]);

            $job->visiting_date = date('Y-m-d H:i:s', strtotime($request->reschedule_date));
            $job->save();

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Job rescheduled successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Something went wrong!']);
        }
    }

    public function changeStatus(Request $request, Job $job)
    {
        $request->validate([
            'status' => 'required|string',
            'cancel_amount' => 'nullable|required_if:status,CANCELLED|numeric|min:0',
            'cancel_note' => 'nullable|required_if:status,CANCELLED|string|max:255'
        ]);

        DB::beginTransaction();

        try {

            $job->status = $request->status;

            if ($request->status === 'CANCELLED') {
                $job->cancellation_amount = $request->cancel_amount;
                $job->cancellation_note = $request->cancel_note;
            }

            $job->save();

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Job rescheduled successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Something went wrong!']);
        }
    }

}
