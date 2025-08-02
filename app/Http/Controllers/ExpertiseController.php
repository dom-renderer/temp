<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expertise;

class ExpertiseController extends Controller
{
    protected $title = 'Expertise';
    protected $view = 'expertises.';

    public function __construct()
    {
        $this->middleware('permission:expertises.index')->only(['index', 'ajax']);
        $this->middleware('permission:expertises.create')->only(['create']);
        $this->middleware('permission:expertises.store')->only(['store']);
        $this->middleware('permission:expertises.edit')->only(['edit']);
        $this->middleware('permission:expertises.update')->only(['update']);
        $this->middleware('permission:expertises.show')->only(['show']);
        $this->middleware('permission:expertises.destroy')->only(['destroy']);
    }

    public function index()
    {
        if (request()->ajax()) {
            return $this->ajax();
        }
        $title = $this->title;
        $subTitle = 'Manage expertise here';
        return view($this->view . 'index', compact('title', 'subTitle'));
    }

    public function ajax()
    {
        $query = Expertise::query();
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
                if (auth()->user()->can('expertises.edit')) {
                    $html .= '<a href="' . route('expertises.edit', encrypt($row->id)) . '" class="btn btn-sm btn-primary"> <i class="fa fa-edit"> </i> </a>&nbsp;';
                }
                if (auth()->user()->can('expertises.destroy')) {
                    $html .= '<button type="button" class="btn btn-sm btn-danger" id="deleteRow" data-row-route="' . route('expertises.destroy', $row->id) . '"> <i class="fa fa-trash"> </i> </button>&nbsp;';
                }
                if (auth()->user()->can('expertises.show')) {
                    $html .= '<a href="' . route('expertises.show', encrypt($row->id)) . '" class="btn btn-sm btn-secondary"> <i class="fa fa-eye"> </i> </a>';
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
        $subTitle = 'Add New Expertise';
        return view($this->view . 'create', compact('title', 'subTitle'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:expertises,name',
            'status' => 'required|boolean',
        ]);

        $data = $request->only(['name', 'status']);
        Expertise::create($data);
        
        return redirect()->route('expertises.index')->with('success', 'Expertise created successfully.');
    }

    public function show($id)
    {
        $category = Expertise::findOrFail(decrypt($id));
        $title = $this->title;
        $subTitle = 'Expertise Details';

        return view($this->view . 'view', compact('title', 'subTitle', 'category'));
    }

    public function edit($id)
    {
        $category = Expertise::findOrFail(decrypt($id));
        $title = $this->title;
        $subTitle = 'Edit Expertise';

        return view($this->view . 'edit', compact('title', 'subTitle', 'category'));
    }

    public function update(Request $request, $id)
    {
        $category = Expertise::findOrFail(decrypt($id));
        $request->validate([
            'name' => 'required|string|max:255|unique:expertises,name,' . $category->id,
            'status' => 'required|boolean',
        ]);

        $data = $request->only(['name', 'status']);
        $category->update($data);

        return redirect()->route('expertises.index')->with('success', 'Expertise updated successfully.');
    }

    public function destroy($id)
    {
        $category = Expertise::findOrFail($id);
        $category->delete();

        return response()->json(['success' => 'Expertise deleted successfully.']);
    }
}
