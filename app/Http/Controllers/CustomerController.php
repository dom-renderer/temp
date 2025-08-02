<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use App\Models\User;

class CustomerController extends Controller
{
    protected $title = 'Customers';
    protected $view = 'customers.';

    public function __construct()
    {
        $this->middleware('permission:customers.index')->only(['index', 'ajax']);
        $this->middleware('permission:customers.create')->only(['create']);
        $this->middleware('permission:customers.store')->only(['store']);
        $this->middleware('permission:customers.edit')->only(['edit']);
        $this->middleware('permission:customers.update')->only(['update']);
        $this->middleware('permission:customers.show')->only(['show']);
        $this->middleware('permission:customers.destroy')->only(['destroy']);
    }

    public function index()
    {
        if (request()->ajax()) {
            return $this->ajax();
        }
        $title = $this->title;
        $subTitle = 'Manage customer here';
        return view($this->view . 'index', compact('title', 'subTitle'));
    }

    public function ajax()
    {
        $engineerRole = Role::where('name', 'customer')->first();
        $query = User::query()->whereHas('roles', function ($q) use ($engineerRole) {
            $q->where('id', $engineerRole->id);
        });
        if (request('filter_status') !== null && request('filter_status') !== '') {
            $query->where('status', request('filter_status'));
        }
        return datatables()
            ->eloquent($query)
            ->editColumn('alternate_phone_number', function ($row) {
                return '+' . $row->alternate_dial_code . ' ' . $row->alternate_phone_number;
            })
            ->editColumn('status', function ($row) {
                return $row->status ? '<span class="badge bg-success">Enable</span>' : '<span class="badge bg-danger">Disable</span>';
            })
            ->addColumn('action', function ($row) {
                $html = '';
                if (auth()->user()->can('customers.edit')) {
                    $html .= '<a href="' . route('customers.edit', encrypt($row->id)) . '" class="btn btn-sm btn-primary"> <i class="fa fa-edit"> </i> </a>&nbsp;';
                }
                if (auth()->user()->can('customers.destroy')) {
                    $html .= '<button type="button" class="btn btn-sm btn-danger" id="deleteRow" data-row-route="' . route('customers.destroy', $row->id) . '"> <i class="fa fa-trash"> </i> </button>&nbsp;';
                }
                if (auth()->user()->can('customers.show')) {
                    $html .= '<a href="' . route('customers.show', encrypt($row->id)) . '" class="btn btn-sm btn-secondary"> <i class="fa fa-eye"> </i> </a>';
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
        $subTitle = 'Add New Customer';
        return view($this->view . 'create', compact('title', 'subTitle'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'alternate_dial_code' => 'required|string|max:10',
            'alternate_phone_number' => 'required|string|max:20',
            'profile' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|boolean',
            'password' => 'required|string|min:6',
            'address_line_1' => 'required|string|max:500',
            'address_line_2' => 'nullable|string|max:500',
            'country' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'pincode' => 'required|string|max:20',
            'latitude' => 'nullable|string|max:50',
            'longitude' => 'nullable|string|max:50',
            'location_url' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $data = $request->only(['name', 'email', 'alternate_dial_code', 'alternate_phone_number', 'status', 'alternate_name', 'country', 'state', 'city', 'address_line_1', 'address_line_2', 'latitude', 'longitude', 'location_url', 'pincode']);
            $data['password'] = $request->password;
            $data['added_by'] = optional(auth()->user())->id;
            if ($request->hasFile('profile')) {
                $file = $request->file('profile');
                $filename = uniqid('profile_') . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('storage/users/profile'), $filename);
                $data['profile'] = $filename;
            }
            $user = User::create($data);
            $engineerRole = Role::where('name', 'customer')->first();
            $user->roles()->attach($engineerRole->id);

            DB::commit();

            if ($request->has('response_type') && $request->response_type == 'json') {
                return response()->json(['status' => true, 'message' => 'Customer created successfully.', 'user' => $user]);
            } else {
                return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
            }
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->has('response_type') && $request->response_type == 'json') {
                return response()->json(['status' => false, 'message' => 'Something Went Wrong.']);
            } else {
                return redirect()->route('customers.index')->with('error', 'Something Went Wrong.');
            }
        }
    }

    public function show($id)
    {
        $engineer = User::findOrFail(decrypt($id));

        $title = $this->title;
        $subTitle = 'Customer Details';
        return view($this->view . 'view', compact('title', 'subTitle', 'engineer'));
    }

    public function edit($id)
    {
        $engineer = User::findOrFail(decrypt($id));

        $title = $this->title;
        $subTitle = 'Edit Customer';
        return view($this->view . 'edit', compact('title', 'subTitle', 'engineer'));
    }

    public function update(Request $request, $id)
    {
        $engineer = User::findOrFail(decrypt($id));
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $engineer->id,
            'alternate_dial_code' => 'required|string|max:10',
            'alternate_phone_number' => 'required|string|max:20',
            'profile' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|boolean',
            'password' => 'nullable|string|min:6',
            'address_line_1' => 'required|string|max:500',
            'address_line_2' => 'nullable|string|max:500',
            'country' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'pincode' => 'required|string|max:20',
            'latitude' => 'nullable|string|max:50',
            'longitude' => 'nullable|string|max:50',
            'location_url' => 'nullable|string',
        ]);
        DB::beginTransaction();
        try {
            $data = $request->only(['name', 'email', 'alternate_dial_code', 'alternate_phone_number', 'status', 'alternate_name', 'country', 'state', 'city', 'address_line_1', 'address_line_2', 'latitude', 'longitude', 'location_url', 'pincode']);

            if ($request->filled('password')) {
                $data['password'] = $request->password;
            }
            
            if ($request->hasFile('profile')) {
                $file = $request->file('profile');
                $filename = uniqid('profile_') . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('storage/users/profile'), $filename);
                $data['profile'] = $filename;
            }

            $engineer->update($data);

            DB::commit();
            return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('customers.index')->with('error', 'Something Went Wrong.');
        }
    }

    public function destroy($id)
    {
        $engineer = User::findOrFail($id);
        $engineer->delete();
        return response()->json(['success' => 'Customer deleted successfully.']);
    }
}
