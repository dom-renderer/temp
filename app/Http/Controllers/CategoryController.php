<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    protected $title = 'Categories';
    protected $view = 'categories.';

    public function __construct()
    {
        $this->middleware('permission:categories.index')->only(['index', 'ajax']);
        $this->middleware('permission:categories.create')->only(['create']);
        $this->middleware('permission:categories.store')->only(['store']);
        $this->middleware('permission:categories.edit')->only(['edit']);
        $this->middleware('permission:categories.update')->only(['update']);
        $this->middleware('permission:categories.show')->only(['show']);
        $this->middleware('permission:categories.destroy')->only(['destroy']);
    }

    public function index()
    {
        if (request()->ajax()) {
            return $this->ajax();
        }
        $title = $this->title;
        $subTitle = 'Manage categories here';
        return view($this->view . 'index', compact('title', 'subTitle'));
    }

    public function ajax()
    {
        $query = Category::query()->with('parent');
        if (request('filter_status') !== null && request('filter_status') !== '') {
            $query->where('status', request('filter_status'));
        }
        return datatables()
            ->eloquent($query)
            ->addColumn('parent_name', function ($row) {
                return $row->parent ? $row->parent->name : '-';
            })
            ->editColumn('status', function ($row) {
                return $row->status ? '<span class="badge bg-success">Enable</span>' : '<span class="badge bg-danger">Disable</span>';
            })
            ->addColumn('action', function ($row) {
                $html = '';
                if (auth()->user()->can('categories.edit')) {
                    $html .= '<a href="' . route('categories.edit', encrypt($row->id)) . '" class="btn btn-sm btn-primary"> <i class="fa fa-edit"> </i> </a>&nbsp;';
                }
                if (auth()->user()->can('categories.destroy')) {
                    $html .= '<button type="button" class="btn btn-sm btn-danger" id="deleteRow" data-row-route="' . route('categories.destroy', $row->id) . '"> <i class="fa fa-trash"> </i> </button>&nbsp;';
                }
                if (auth()->user()->can('categories.show')) {
                    $html .= '<a href="' . route('categories.show', encrypt($row->id)) . '" class="btn btn-sm btn-secondary"> <i class="fa fa-eye"> </i> </a>';
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
        $subTitle = 'Add New Category';
        return view($this->view . 'create', compact('title', 'subTitle'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'parent_id' => 'nullable|exists:categories,id',
            'status' => 'required|boolean',
        ]);
        $data = $request->only(['name', 'parent_id', 'status']);
        $data['slug'] = Str::slug($data['name']);
        Category::create($data);
        
        return redirect()->route('categories.index')->with('success', 'Category created successfully.');
    }

    public function show($id)
    {
        $category = Category::with('parent')->findOrFail(decrypt($id));
        $title = $this->title;
        $subTitle = 'Category Details';

        return view($this->view . 'view', compact('title', 'subTitle', 'category'));
    }

    public function edit($id)
    {
        $category = Category::findOrFail(decrypt($id));
        $title = $this->title;
        $subTitle = 'Edit Category';
        $parentCategory = Category::find($category->parent_id);

        return view($this->view . 'edit', compact('title', 'subTitle', 'category', 'parentCategory'));
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail(decrypt($id));
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'parent_id' => 'nullable|exists:categories,id',
            'status' => 'required|boolean',
        ]);
        $data = $request->only(['name', 'parent_id', 'status']);
        $data['slug'] = Str::slug($data['name']);
        $category->update($data);

        return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json(['success' => 'Category deleted successfully.']);
    }
}
