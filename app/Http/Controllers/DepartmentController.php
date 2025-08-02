<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;

class DepartmentController extends Controller
{
    protected $title = 'Departments';
    protected $view = 'departments.';

    public function __construct()
    {
        $this->middleware('permission:departments.index')->only(['index', 'ajax']);
        $this->middleware('permission:departments.create')->only(['create']);
        $this->middleware('permission:departments.store')->only(['store']);
        $this->middleware('permission:departments.edit')->only(['edit']);
        $this->middleware('permission:departments.update')->only(['update']);
        $this->middleware('permission:departments.show')->only(['show']);
        $this->middleware('permission:departments.destroy')->only(['destroy']);
    }

    public function index()
    {
        if (request()->ajax()) {
            return $this->ajax();
        }
        $title = $this->title;
        $subTitle = 'Manage departments here';
        return view($this->view . 'index', compact('title', 'subTitle'));
    }

    public function ajax()
    {
        $query = Department::query();
        if (request('filter_status') !== null && request('filter_status') !== '') {
            $query->where('status', request('filter_status'));
        }
        return datatables()
            ->eloquent($query)
            ->editColumn('status', function ($row) {
                return $row->status ? '<span class="badge bg-success">Enable</span>' : '<span class="badge bg-danger">Disable</span>';
            })
            ->addColumn('action', function ($row) {
                $html = '';
                if (auth()->user()->can('departments.edit')) {
                    $html .= '<a href="' . route('departments.edit', encrypt($row->id)) . '" class="btn btn-sm btn-primary"> <i class="fa fa-edit"> </i> </a>&nbsp;';
                }
                if (auth()->user()->can('departments.destroy')) {
                    $html .= '<button type="button" class="btn btn-sm btn-danger" id="deleteRow" data-row-route="' . route('departments.destroy', $row->id) . '"> <i class="fa fa-trash"> </i> </button>&nbsp;';
                }
                if (auth()->user()->can('departments.show')) {
                    $html .= '<a href="' . route('departments.show', encrypt($row->id)) . '" class="btn btn-sm btn-secondary"> <i class="fa fa-eye"> </i> </a>';
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
        $subTitle = 'Add New Department';
        return view($this->view . 'create', compact('title', 'subTitle'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:departments,name',
            'status' => 'required|boolean',
        ]);

        $data = $request->only(['name', 'status']);
        Department::create($data);
        
        return redirect()->route('departments.index')->with('success', 'Department created successfully.');
    }

    public function show($id)
    {
        $category = Department::findOrFail(decrypt($id));
        $title = $this->title;
        $subTitle = 'Department Details';

        return view($this->view . 'view', compact('title', 'subTitle', 'category'));
    }

    public function edit($id)
    {
        $category = Department::findOrFail(decrypt($id));
        $title = $this->title;
        $subTitle = 'Edit Department';

        return view($this->view . 'edit', compact('title', 'subTitle', 'category'));
    }

    public function update(Request $request, $id)
    {
        $category = Department::findOrFail(decrypt($id));
        $request->validate([
            'name' => 'required|string|max:255|unique:departments,name,' . $category->id,
            'status' => 'required|boolean',
        ]);

        $data = $request->only(['name', 'status']);
        $category->update($data);

        return redirect()->route('departments.index')->with('success', 'Department updated successfully.');
    }

    public function destroy($id)
    {
        $category = Department::findOrFail($id);
        $category->delete();

        return response()->json(['success' => 'Department deleted successfully.']);
    }
}
