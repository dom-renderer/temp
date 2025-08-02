<?php

namespace App\Http\Controllers;

use App\Models\ExpertiseUser;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use App\Models\DepartmentUser;
use App\Models\Expertise;
use Illuminate\Http\Request;
use App\Models\User;

class TechnicianController extends Controller
{
    protected $title = 'Technicians';
    protected $view = 'technicians.';

    public function __construct()
    {
        $this->middleware('permission:technicians.index')->only(['index', 'ajax']);
        $this->middleware('permission:technicians.create')->only(['create']);
        $this->middleware('permission:technicians.store')->only(['store']);
        $this->middleware('permission:technicians.edit')->only(['edit']);
        $this->middleware('permission:technicians.update')->only(['update']);
        $this->middleware('permission:technicians.show')->only(['show']);
        $this->middleware('permission:technicians.destroy')->only(['destroy']);
    }

    public function index()
    {
        if (request()->ajax()) {
            return $this->ajax();
        }
        $title = $this->title;
        $subTitle = 'Manage technicians here';
        return view($this->view . 'index', compact('title', 'subTitle'));
    }

    public function ajax()
    {
        $engineerRole = Role::where('name', 'technician')->first();
        $query = User::query()->whereHas('roles', function ($q) use ($engineerRole) {
            $q->where('id', $engineerRole->id);
        });
        if (request('filter_status') !== null && request('filter_status') !== '') {
            $query->where('status', request('filter_status'));
        }
        return datatables()
            ->eloquent($query)
            ->editColumn('phone_number', function ($row) {
                return '+' . $row->dial_code . ' ' . $row->phone_number;
            })
            ->editColumn('status', function ($row) {
                return $row->status ? '<span class="badge bg-success">Enable</span>' : '<span class="badge bg-danger">Disable</span>';
            })
            ->addColumn('action', function ($row) {
                $html = '';
                if (auth()->user()->can('technicians.edit')) {
                    $html .= '<a href="' . route('technicians.edit', encrypt($row->id)) . '" class="btn btn-sm btn-primary"> <i class="fa fa-edit"> </i> </a>&nbsp;';
                }
                if (auth()->user()->can('technicians.destroy')) {
                    $html .= '<button type="button" class="btn btn-sm btn-danger" id="deleteRow" data-row-route="' . route('technicians.destroy', $row->id) . '"> <i class="fa fa-trash"> </i> </button>&nbsp;';
                }
                if (auth()->user()->can('technicians.show')) {
                    $html .= '<a href="' . route('technicians.show', encrypt($row->id)) . '" class="btn btn-sm btn-secondary"> <i class="fa fa-eye"> </i> </a>';
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
        $subTitle = 'Add New Technician';
        return view($this->view . 'create', compact('title', 'subTitle'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'dial_code' => 'required|string|max:10',
            'phone_number' => ['required', 'regex:/^[0-9]+$/', 'max:15', 'unique:users,phone_number'],
            'profile' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|boolean',
            'password' => 'required|string|min:6',
        ]);

        DB::beginTransaction();

        try {
            $data = $request->only(['name', 'email', 'dial_code', 'phone_number', 'status']);
            $data['password'] = $request->password;
            $data['added_by'] = optional(auth()->user())->id;
            if ($request->hasFile('profile')) {
                $file = $request->file('profile');
                $filename = uniqid('profile_') . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('storage/users/profile'), $filename);
                $data['profile'] = $filename;
            }
            $user = User::create($data);
            $engineerRole = Role::where('name', 'technician')->first();
            $user->roles()->attach($engineerRole->id);

            if ($request->has('departments')) {
                foreach ($request->departments as $department) {
                    DepartmentUser::create([
                        'user_id' => $user->id,
                        'department_id' => $department
                    ]);
                }
            }

            if ($request->has('expertises')) {
                foreach ($request->expertises as $expertise) {
                    ExpertiseUser::create([
                        'user_id' => $user->id,
                        'expertise_id' => $expertise
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('technicians.index')->with('success', 'Technician created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('technicians.index')->with('error', 'Something Went Wrong.');
        }
    }

    public function show($id)
    {
        $engineer = User::findOrFail(decrypt($id));
        $currentDepartments = DepartmentUser::with('department')->where('user_id', $engineer->id)->get();
        $currentExpertises = ExpertiseUser::with('expertise')->where('user_id', $engineer->id)->get();

        $title = $this->title;
        $subTitle = 'Technician Details';
        return view($this->view . 'view', compact('title', 'subTitle', 'engineer', 'currentDepartments', 'currentExpertises'));
    }

    public function edit($id)
    {
        $engineer = User::findOrFail(decrypt($id));
        $currentDepartments = DepartmentUser::with('department')->where('user_id', $engineer->id)->get();
        $currentExpertises = ExpertiseUser::with('expertise')->where('user_id', $engineer->id)->get();

        $title = $this->title;
        $subTitle = 'Edit Technician';
        return view($this->view . 'edit', compact('title', 'subTitle', 'engineer', 'currentDepartments', 'currentExpertises'));
    }

    public function update(Request $request, $id)
    {
        $engineer = User::findOrFail(decrypt($id));
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $engineer->id,
            'dial_code' => 'required|string|max:10',
            'phone_number' => ['required', 'regex:/^[0-9]+$/', 'max:15', 'unique:users,phone_number,' . $engineer->id],
            'profile' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|boolean',
            'password' => 'nullable|string|min:6',
        ]);
        DB::beginTransaction();
        try {
            $data = $request->only(['name', 'email', 'dial_code', 'phone_number', 'status']);
            
            if ($request->filled('password')) {
                $data['password'] = $request->password;
            }
            
            if ($request->hasFile('profile')) {
                $file = $request->file('profile');
                $filename = uniqid('profile_') . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('storage/users/profile'), $filename);
                $data['profile'] = $filename;
            }

            $deptsToKeep = $exptsToKeep = [];

            if ($request->has('departments')) {
                foreach ($request->departments as $department) {
                    $deptsToKeep [] = DepartmentUser::updateOrCreate([
                        'user_id' => $engineer->id,
                        'department_id' => $department
                    ])->id;
                }
            }

            if ($request->has('expertises')) {
                foreach ($request->expertises as $expertise) {
                    $exptsToKeep [] = ExpertiseUser::create([
                        'user_id' => $engineer->id,
                        'expertise_id' => $expertise
                    ])->id;
                }
            }            

            $engineer->update($data);

            if (!empty($deptsToKeep)) {
                DepartmentUser::whereNotIn('id', $deptsToKeep)->where('user_id', $engineer->id)->delete();
            } else {
                DepartmentUser::where('user_id', $engineer->id)->delete();
            }

            if (!empty($exptsToKeep)) {
                ExpertiseUser::whereNotIn('id', $exptsToKeep)->where('user_id', $engineer->id)->delete();
            } else {
                ExpertiseUser::where('user_id', $engineer->id)->delete();
            }

            DB::commit();
            return redirect()->route('technicians.index')->with('success', 'Technician updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('technicians.index')->with('error', 'Something Went Wrong.');
        }
    }

    public function destroy($id)
    {
        $engineer = User::findOrFail($id);
        $engineer->delete();
        return response()->json(['success' => 'Technician deleted successfully.']);
    }
} 