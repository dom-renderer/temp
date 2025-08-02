<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    protected $title = 'Products';
    protected $view = 'products.';

    public function __construct()
    {
        $this->middleware('permission:products.index')->only(['index', 'ajax']);
        $this->middleware('permission:products.create')->only(['create']);
        $this->middleware('permission:products.store')->only(['store']);
        $this->middleware('permission:products.edit')->only(['edit']);
        $this->middleware('permission:products.update')->only(['update']);
        $this->middleware('permission:products.show')->only(['show']);
        $this->middleware('permission:products.destroy')->only(['destroy']);
    }

    public function index()
    {
        if (request()->ajax()) {
            return $this->ajax();
        }
        $title = $this->title;
        $subTitle = 'Manage products here';
        return view($this->view . 'index', compact('title', 'subTitle'));
    }

    public function ajax()
    {
        $query = Product::query()->with('category');
        if (request('filter_status') !== null && request('filter_status') !== '') {
            $query->where('status', request('filter_status'));
        }
        if (request('filter_category') !== null && request('filter_category') !== '') {
            $query->where('category_id', request('filter_category'));
        }
        
        return datatables()
            ->eloquent($query)
            ->editColumn('amount', function ($row) {
                return number_format($row->amount, 2);
            })
            ->addColumn('category_name', function ($row) {
                return $row->category ? $row->category->name : '-';
            })
            ->editColumn('status', function ($row) {
                return $row->status ? '<span class="badge bg-success">Enable</span>' : '<span class="badge bg-danger">Disable</span>';
            })
            ->addColumn('action', function ($row) {
                $html = '';
                if (auth()->user()->can('products.edit')) {
                    $html .= '<a href="' . route('products.edit', encrypt($row->id)) . '" class="btn btn-sm btn-primary"> <i class="fa fa-edit"> </i> </a>&nbsp;';
                }
                if (auth()->user()->can('products.destroy')) {
                    $html .= '<button type="button" class="btn btn-sm btn-danger" id="deleteRow" data-row-route="' . route('products.destroy', $row->id) . '"> <i class="fa fa-trash"> </i> </button>&nbsp;';
                }
                if (auth()->user()->can('products.show')) {
                    $html .= '<a href="' . route('products.show', encrypt($row->id)) . '" class="btn btn-sm btn-secondary"> <i class="fa fa-eye"> </i> </a>&nbsp;';
                }
                $html .= '<a href="' . route('products-media', encrypt($row->id)) . '" class="btn btn-sm btn-info"> <i class="fa fa-images"> </i> </a>';
                return $html;
            })
            ->rawColumns(['status', 'action'])
            ->addIndexColumn()
            ->toJson();
    }

    public function create()
    {
        $title = $this->title;
        $subTitle = 'Add New Product';
        return view($this->view . 'create', compact('title', 'subTitle'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:products,sku',
            'description_1' => 'nullable|string',
            'description_2' => 'nullable|string',
            'amount' => 'required|numeric',
            'status' => 'required|boolean',
        ]);
        $data = $request->only(['category_id', 'name', 'sku', 'description_1', 'description_2', 'amount', 'status']);
        Product::create($data);
        return redirect()->route('products.index')->with('success', 'Product created successfully.');
    }

    public function show($id)
    {
        $product = Product::with('category')->findOrFail(decrypt($id));
        $title = $this->title;
        $subTitle = 'Product Details';
        return view($this->view . 'view', compact('title', 'subTitle', 'product'));
    }

    public function edit($id)
    {
        $product = Product::findOrFail(decrypt($id));
        $title = $this->title;
        $subTitle = 'Edit Product';
        $category = Category::find($product->category_id);
        return view($this->view . 'edit', compact('title', 'subTitle', 'product', 'category'));
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail(decrypt($id));
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:products,sku,' . $product->id,
            'description_1' => 'nullable|string',
            'description_2' => 'nullable|string',
            'amount' => 'required|numeric',
            'status' => 'required|boolean',
        ]);
        $data = $request->only(['category_id', 'name', 'sku', 'description_1', 'description_2', 'amount', 'status']);
        $product->update($data);
        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return response()->json(['success' => 'Product deleted successfully.']);
    }

    public function images($id)
    {
        $product = Product::findOrFail(decrypt($id));
        $title = $this->title;
        $subTitle = 'Manage Product Images';
        return view($this->view . 'images', compact('title', 'subTitle', 'product'));
    }
}
