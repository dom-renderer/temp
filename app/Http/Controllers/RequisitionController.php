<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\RequisitionItem;
use Illuminate\Http\Request;
use App\Models\Requisition;
use App\Models\Product;
use App\Helpers\Helper;
use App\Models\User;
use App\Models\Job;

class RequisitionController extends Controller
{
    protected $title = 'Requisitions';
    protected $view = 'requisitions.';

    public function __construct()
    {
        $this->middleware('permission:requisitions.index')->only(['index', 'ajax']);
        $this->middleware('permission:requisitions.create')->only(['create']);
        $this->middleware('permission:requisitions.store')->only(['store']);
        $this->middleware('permission:requisitions.edit')->only(['edit']);
        $this->middleware('permission:requisitions.update')->only(['update']);
        $this->middleware('permission:requisitions.show')->only(['show']);
        $this->middleware('permission:requisitions.destroy')->only(['destroy']);
    }

    public function index()
    {
        if (request()->ajax()) {
            return $this->ajax();
        }

        $title = $this->title;
        $subTitle = 'List';
        return view($this->view . 'index', compact('title', 'subTitle'));
    }

    public function ajax()
    {
        $query = Requisition::query()->with(['job', 'addedBy']);

        if (request('filter_status') !== null && request('filter_status') !== '') {
            $query->where('status', request('filter_status'));
        }

        if (request('filter_job') !== null && request('filter_job') !== '') {
            $query->where('job_id', request('filter_job'));
        }

        return datatables()
            ->eloquent($query)
            ->addColumn('job_code', function ($row) {
                return $row->job ? $row->job->code : '-';
            })
            ->addColumn('added_by_name', function ($row) {
                return $row->addedBy ? $row->addedBy->name : '-';
            })
            ->editColumn('status', function ($row) {
                switch ($row->status) {
                    case 'PENDING':
                        return '<span class="badge bg-warning">Pending</span>';
                    case 'APPROVED':
                        return '<span class="badge bg-success">Approved</span>';
                    case 'REJECTED':
                        return '<span class="badge bg-danger">Rejected</span>';
                    default:
                        return '<span class="badge bg-secondary">Unknown</span>';
                }
            })
            ->editColumn('total', function ($row) {
                return number_format($row->items()->sum('total'), 2);
            })
            ->editColumn('created_at', function ($row) {
                return date('d-m-Y H:i', strtotime($row->created_at));
            })
            ->addColumn('action', function ($row) {
                $html = '';
                if (auth()->user()->can('requisitions.edit')) {
                    $html .= '<a href="' . route('requisitions.edit', encrypt($row->id)) . '" class="btn btn-sm btn-primary"> <i class="fa fa-edit"> </i> </a>&nbsp;';
                }
                if (auth()->user()->can('requisitions.destroy')) {
                    $html .= '<button type="button" class="btn btn-sm btn-danger" id="deleteRow" data-row-route="' . route('requisitions.destroy', $row->id) . '"> <i class="fa fa-trash"> </i> </button>&nbsp;';
                }
                if (auth()->user()->can('requisitions.show')) {
                    $html .= '<a href="' . route('requisitions.show', encrypt($row->id)) . '" class="btn btn-sm btn-secondary"> <i class="fa fa-eye"> </i> </a>';
                }
                return $html;
            })
            ->rawColumns(['type', 'status', 'action'])
            ->addIndexColumn()
            ->toJson();
    }

    public function create()
    {
        $title = $this->title;
        $subTitle = 'Add';
        return view($this->view . 'create', compact('title', 'subTitle'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'job_id' => 'required|exists:job,id',
            'requisition' => 'required|array|min:1',
            'requisition.*.type' => 'required|in:INVENTORY,VENDOR',
            'requisition.*.product' => 'required',
            'requisition.*.description' => 'nullable|string',
            'requisition.*.amount' => 'required|numeric|min:0',
            'requisition.*.quantity' => 'required|numeric|min:1',
            'requisition.*.total' => 'required|numeric|min:0'
        ]);

        DB::beginTransaction();
        
        try {

            $requisitionEloquent = new Requisition();
            $requisitionEloquent->job_id = $request->job_id;
            $requisitionEloquent->code = Helper::requisitionCode();
            $requisitionEloquent->added_by = auth()->user()->id;
            $requisitionEloquent->status = 'PENDING';
            $requisitionEloquent->save();

            foreach ($request->input('requisition') as $item) {
                $requisitionItem = new RequisitionItem();
                $requisitionItem->requisition_id = $requisitionEloquent->id;
                $requisitionItem->type = $item['type'];
                
                if ($item['type'] === 'INVENTORY') {
                    $requisitionItem->product_id = $item['product'];
                    $requisitionItem->vendor_id = null;
                } else {
                    $requisitionItem->product_id = $item['product'];
                    $requisitionItem->vendor_id = $item['vendor'] ?? null;
                }

                $requisitionItem->description = $item['description'] ?? null;
                $requisitionItem->amount = $item['amount'];
                $requisitionItem->quantity = $item['quantity'];
                $requisitionItem->total = $item['total'];
                $requisitionItem->save();
            }

            DB::commit();
            return redirect()->route('requisitions.index')->with('success', 'Requisition created successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to create requisition.')->withInput();
        }
    }

    public function show($id)
    {
        try {
            $requisition = Requisition::with(['job', 'items.product', 'addedBy'])
                ->findOrFail(decrypt($id));
            
            $title = $this->title;
            $subTitle = 'Details';
            
            return view($this->view . 'show', compact('requisition', 'title', 'subTitle'));
        } catch (\Exception $e) {
            return redirect()->route('requisitions.index')->with('error', 'Requisition not found.');
        }
    }

    public function edit($id)
    {
        try {
            $requisition = Requisition::with(['job', 'items.product'])->findOrFail(decrypt($id));
            
            if ($requisition->status !== 'PENDING') {
                return redirect()->route('requisitions.index')->with('error', 'Requisition cannot be edited in current status.');
            }
            
            $title = $this->title;
            $subTitle = 'Edit';
            return view($this->view . 'edit', compact('requisition', 'title', 'subTitle'));
        } catch (\Exception $e) {
            return redirect()->route('requisitions.index')->with('error', 'Requisition not found.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $requisition = Requisition::findOrFail(decrypt($id));
            
            if ($requisition->status !== 'PENDING') {
                return redirect()->route('requisitions.index')->with('error', 'Requisition cannot be edited in current status.');
            }

            $request->validate([
                'job_id' => 'required|exists:job,id',
                'requisition' => 'required|array|min:1',
                'requisition.*.type' => 'required|in:INVENTORY,VENDOR',
                'requisition.*.product' => 'required',
                'requisition.*.description' => 'nullable|string',
                'requisition.*.amount' => 'required|numeric|min:0',
                'requisition.*.quantity' => 'required|numeric|min:1',
                'requisition.*.total' => 'required|numeric|min:0'
            ]);

            DB::beginTransaction();

            $requisition->job_id = $request->job_id;
            $requisition->save();

            $existingItemIds = $requisition->items->pluck('id')->toArray();
            $updatedItemIds = [];

            foreach ($request->input('requisition') as $item) {
                if (isset($item['id']) && !empty($item['id'])) {

                    $requisitionItem = RequisitionItem::find($item['id']);
                    if ($requisitionItem && $requisitionItem->requisition_id == $requisition->id) {
                        $requisitionItem->type = $item['type'];
                        
                        if ($item['type'] === 'INVENTORY') {
                            $requisitionItem->product_id = $item['product'];
                            $requisitionItem->vendor_id = null;
                        } else {
                            $requisitionItem->product_id = $item['product'];
                            $requisitionItem->vendor_id = $item['vendor'] ?? null;
                        }

                        $requisitionItem->description = $item['description'] ?? null;
                        $requisitionItem->amount = $item['amount'];
                        $requisitionItem->quantity = $item['quantity'];
                        $requisitionItem->total = $item['total'];
                        $requisitionItem->save();

                        $updatedItemIds[] = $requisitionItem->id;
                    }
                } else {

                    $requisitionItem = new RequisitionItem();
                    $requisitionItem->requisition_id = $requisition->id;
                    $requisitionItem->type = $item['type'];
                    
                    if ($item['type'] === 'INVENTORY') {
                        $requisitionItem->product_id = $item['product'];
                            $requisitionItem->vendor_id = null;
                    } else {
                        $requisitionItem->product_id = $item['product'];
                        $requisitionItem->vendor_id = $item['vendor'] ?? null;
                    }

                    $requisitionItem->description = $item['description'] ?? null;
                    $requisitionItem->amount = $item['amount'];
                    $requisitionItem->quantity = $item['quantity'];
                    $requisitionItem->total = $item['total'];
                    $requisitionItem->save();

                    $updatedItemIds[] = $requisitionItem->id;
                }
            }

            $itemsToDelete = array_diff($existingItemIds, $updatedItemIds);
            if (!empty($itemsToDelete)) {
                RequisitionItem::whereIn('id', $itemsToDelete)->delete();
            }

            DB::commit();
            return redirect()->route('requisitions.index')->with('success', 'Requisition updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update requisition: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $requisition = Requisition::findOrFail($id);
            
            if ($requisition->status !== 'PENDING') {
                return response()->json(['error' => 'Requisition cannot be deleted in current status.'], 400);
            }

            DB::beginTransaction();
            
            $requisition->items()->delete();
            
            $requisition->delete();
            
            DB::commit();
            return response()->json(['success' => 'Requisition deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Something Went Wrong: ' . $e->getMessage()], 500);
        }
    }
} 