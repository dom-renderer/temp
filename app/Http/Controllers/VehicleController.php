<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vehicle;

class VehicleController extends Controller
{
    public function index(Request $request)
    {   
        if ($request->ajax()) {

            return datatables()
            ->eloquent(Vehicle::query())
            ->addColumn('action', function ($row) {
                $action = '';

                if (auth()->user()->can('vehicles.show')) {
                    $action .= '<a href="'.route("vehicles.show", encrypt($row->id)).'" class="btn btn-warning btn-sm me-2 btn-action"> <i class="bi bi-eye"></i> </a>';
                }

                if (auth()->user()->can('vehicles.edit')) {
                    $action .= '<a href="'.route('vehicles.edit', encrypt($row->id)).'" class="btn btn-info btn-sm me-2 btn-action"><i class="bi bi-pencil-square"></i></a>';
                }

                if (auth()->user()->can('vehicles.destroy')) {
                    $action .= '<form method="POST" action="'.route("vehicles.destroy", encrypt($row->id)).'" style="display:inline;"><input type="hidden" name="_method" value="DELETE"><input type="hidden" name="_token" value="'.csrf_token().'"><button type="submit" class="btn btn-danger btn-sm deleteGroup btn-delete"><i class="bi bi-trash"></i></button></form>';
                }

                return $action;
            })
            ->editColumn('status', function ($row) {
                if ($row->status == 1) {
                    return '<div class="deliver-label badge bg-success">Active</div>';
                } else if ($row->status == 2) {
                    return '<div class="deliver-label badge bg-warning">In-Maintenance</div>';
                } else if ($row->status == 3) {
                    return '<div class="deliver-label badge bg-primary">Reserved</div>';
                } else {
                    return '<div class="deliver-label badge bg-danger">In-Active</div>';
                }
            })
            ->rawColumns(['action', 'status'])
            ->addIndexColumn()
            ->toJson();
        }

        $page_title = 'Vehicle';
        $page_description = 'Manage vehicles here';
        return view('vehicles.index',compact('page_title', 'page_description'));
    }

    public function create()
    {
        $page_title = 'Vehicle Add';

        return view('vehicles.create', compact( 'page_title'));
    }
    
    public function store(Request $request)
    {
        $this->validate($request, [
            'code' => ['required', function ($name, $value, $fail){
                if (Vehicle::where(\DB::raw('LOWER(code)'), strtolower($value))->exists()) {
                    $fail("Code with this name is already exists.");
                }
            }],
            'vehicle_type_id' => ['required', 'exists:vehicle_types,id'],
            'driver_id' => ['required', 'exists:users,id'],
            'second_man_id' => ['nullable', 'exists:users,id'],
            'account_id' => ['required', 'exists:accounts,id']
        ]);

        if (Vehicle::where('driver_id', $request->driver_id)->where('status', 1)->exists()) {
            return redirect()->back()->with('error','Driver is already assigned to an active vehicle');
        }
    
        Vehicle::create([
            'code' => $request->code,
            'vehicle_type_id' => $request->vehicle_type_id,
            'driver_id' => $request->driver_id,
            'second_man_id' => $request->second_man_id,
            'account_id' => $request->account_id,
            'status' => $request->status
        ]);
    
        return redirect()->route('vehicles.index')->with('success','Account created successfully');
    }

    public function show($id)
    {
        $page_title = 'Vehicle Show';
        $vehicle = Vehicle::find(decrypt($id));
    
        return view('vehicles.show', compact('vehicle', 'page_title'));
    }

    public function edit($id)
    {
        $page_title = 'Vehicle Edit';
        $vehicle = Vehicle::find(decrypt($id));
    
        return view('vehicles.edit', compact('vehicle', 'page_title', 'id'));
    }
    
    public function update(Request $request, $id)
    {
        $vehicleId = decrypt($id);

        $this->validate($request, [
            'code' => ['required', function ($name, $value, $fail) use ($vehicleId) {
                if (Vehicle::where('id', '!=', $vehicleId)->where(\DB::raw('LOWER(code)'), strtolower($value))->exists()) {
                    $fail("Code with this name is already exists.");
                }
            }],
            'vehicle_type_id' => ['required', 'exists:vehicle_types,id'],
            'driver_id' => ['required', 'exists:users,id'],
            'second_man_id' => ['nullable', 'exists:users,id'],
            'account_id' => ['required', 'exists:accounts,id']
        ]);

        if (Vehicle::where('driver_id', $request->driver_id)->where('id', '!=', $vehicleId)->where('status', 1)->exists()) {
            return redirect()->back()->with('error','Driver is already assigned to an active vehicle');
        }        

        $vehicles = Vehicle::find($vehicleId);
        $vehicles->update([
            'code' => $request->code,
            'vehicle_type_id' => $request->vehicle_type_id,
            'driver_id' => $request->driver_id,
            'second_man_id' => $request->second_man_id,
            'account_id' => $request->account_id,
            'status' => $request->status
        ]);
    
        return redirect()->route('vehicles.index')->with('success','Vehicle updated successfully');
    }

    public function destroy($id)
    {
        $account = Vehicle::find(decrypt($id));
        $account->delete();
        
        return redirect()->route('vehicles.index')->with('success','Vehicle deleted successfully');
    }

    public function get(Request $request)
    {
        $queryString = trim($request->searchQuery);
        $excludeId = $request->id;
        $page = $request->input('page', 1);
        $limit = env('SELECT2_PAGE_LENGTH', 5);
        $statuses = $request->status;
    
        $query = Vehicle::with('vtype')->when(is_numeric($excludeId) && $excludeId != '0', function ($builder) use ($excludeId) { 
            return $builder->where('id', '!=', $excludeId);
        });
    
        if (!empty($statuses)) {
            $statusesExploded = explode(',', $statuses);
            if (!empty($statusesExploded)) {
                $query = $query->whereIn('status', $statusesExploded);
            }
        } else {
            $query = $query->whereIn('status', [1, 3]);
        }

        if (!empty($queryString)) {
            $query->where('code', 'LIKE', "%{$queryString}%")
            ->orWhereHas('vtype', function ($builder2) use ($queryString) {
                $builder2->where('description', 'LIKE', "%{$queryString}%");
            });
        }
    
        $data = $query->paginate($limit, ['*'], 'page', $page);
    
        return response()->json([
            'items' => $data->map(function ($pro) {
                return [
                    'id' => $pro->id,
                    'text' => isset($pro->vtype->description) ? "{$pro->vtype->description} - {$pro->code}" : $pro->code,
                    'driver_id' => $pro->driver,
                    'second_man_id' => $pro->second
                ];
            }),
            'pagination' => [
                'more' => $data->hasMorePages()
            ]
        ]);
    }
}
