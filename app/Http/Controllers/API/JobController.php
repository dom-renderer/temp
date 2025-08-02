<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\TimeSpentOnJob;
use Illuminate\Http\Request;
use App\Models\Job;
use Carbon\Carbon;

class JobController extends \App\Http\Controllers\Controller
{

    public function login(Request $request) {

        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|exists:users,phone_number',
            'dial_code' => 'required|exists:users,dial_code',
            'password' => 'required'
        ]);

        if ($validator->fails()) { 
            $errorString = implode(",",$validator->messages()->all());
            return response()->json(['status' => false, 'message' => $errorString], 401);
        }

        if (Auth::attempt($request->only('dial_code', 'phone_number', 'password'))) {

            $user = Auth::user();
            $roles = $user->roles->pluck('id', 'name')->toArray();

            if (empty($roles)) {
                return response()->json(['status' => false, 'message' => 'User is not valid for the login.'], 401);
            } else if (!isset($roles['technician'])) {
                return response()->json(['status' => false, 'message' => 'User is not valid for the login!'], 401);
            }

            if ($user->status != 1) {
                return response()->json(['status' => false, 'message' => 'Your account is disabled by administrator!'], 401);
            } else {
                $success = [
                    'token' => $user->createToken('DMS')->accessToken,
                    'id' => $user->id,
                    'email' => $user->email,
                    'dial_code' => $user->dial_code,
                    'phone_number' => $user->phone_number,
                    'profile' => $user->userprofile,
                    'currency' => $user->currencyr,
                    'address_line_1' => $user->address_line_1,
                    'address_line_2' => $user->address_line_2,
                    'city' => $user->city,
                    'state' => $user->state,
                    'country' => $user->country,
                    'pincode' => $user->pincode,
                    'roles' => collect($roles)->map(function ($value, $key) {
                        return ['id' => $value, 'name' => $key];
                    })->values()
                ];

                return response()->json(['status' => true, 'message' => 'Succeed', 'data' => $success]);
            }
        } else {
            return response()->json(['status' => false, 'message' => 'Unauthorised'], 401);
        }
    }

    public function jobs(Request $request)
    {
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);

        $query = Job::with(['customer', 'assigner', 'technicians.technician', 'materials.product', 'expertise.expertise', 'requisitions.product', 'requisitions.addedby', 'requisitions.approvedby', 'requisitions.rejectedby'])
        ->whereHas('technicians', fn ($builder) => $builder->where('technician_id', auth()->user()->id))
        ->when(in_array($request->status, ['PENDING','INPROGRESS','COMPLETED','CANCELLED']), fn ($builder) => $builder->where('status', request('status')))
        ->when($request->filled('from'), fn ($builder) => $builder->where(DB::raw("DATE_FORMAT(visiting_date, '%Y-%m-%d')"), '>=', date('Y-m-d', strtotime($request->from))))
        ->when($request->filled('to'), fn ($builder) => $builder->where(DB::raw("DATE_FORMAT(visiting_date, '%Y-%m-%d')"), '<=', date('Y-m-d', strtotime($request->to))))
        ->when(!$request->filled('from') && !$request->filled('to'),fn ($builder) => $builder->where(DB::raw("DATE_FORMAT(visiting_date, '%Y-%m-%d')"), date('Y-m-d')));

        $total = $query->count();
        $jobs = $query->skip(($page - 1) * $perPage)
                       ->take($perPage)
                       ->get();

        return response()->json([
            'data' => $jobs,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
            ],
        ]);
    }

    public function punchIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_id' => 'required|exists:job,id',
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) { 
            return response()->json(['status' => false, 'message' => implode(",", $validator->messages()->all())], 401);
        }

        $job = Job::find($request->job_id);

        if (!$job || in_array($job->status, ['COMPLETED', 'CANCELLED'])) {
            return response()->json(['status' => false, 'message' => 'Either job not found or is completed']);
        }

        $technicianId = $request->user_id;

        $existingPunch = TimeSpentOnJob::where('job_id', $job->id)
            ->where('technician_id', $technicianId)
            ->whereNull('punch_out_at')
            ->first();

        if ($existingPunch) {
            return response()->json(['status' => false, 'message' => 'You are already punched in.']);
        }

        $attendance = TimeSpentOnJob::create([
            'job_id' => $job->id,
            'date' => date('Y-m-d'),
            'technician_id' => $technicianId,
            'punch_in_at' => now(),
            'status' => 'PUNCHED_IN',
        ]);

        if ($job->status == 'PENDING') {
            $job->status = 'INPROGRESS';
            $job->save();
        }

        $totalTime = $this->calculateTotalTime($job->id, $technicianId);

        return response()->json(['status' => true, 'message' => 'Punched in successfully.', 'data' => $attendance, 'total_time_spent' => $totalTime]);
    }

    public function punchOut(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_id' => 'required|exists:job,id',
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) { 
            return response()->json(['status' => false, 'message' => implode(",", $validator->messages()->all())], 401);
        }

        $technicianId = $request->user_id;
        $job = Job::find($request->job_id);

        if (!$job) {
            return response()->json(['status' => false, 'message' => 'Either job not found or is completed']);
        }

        $attendance = TimeSpentOnJob::where('job_id', $job->id)
            ->where('technician_id', $technicianId)
            ->whereNull('punch_out_at')
            ->latest('punch_in_at')
            ->first();

        if (!$attendance) {
            return response()->json(['status' => false, 'message' => 'No active punch-in found.']);
        }

        $attendance->update([
            'punch_out_at' => now(),
            'status' => 'PUNCHED_OUT',
        ]);

        $totalTime = $this->calculateTotalTime($job->id, $technicianId);

        return response()->json(['status' => true, 'message' => 'Punched out successfully.', 'data' => $attendance, 'total_time_spent' => $totalTime]);
    }

    private function calculateTotalTime($jobId, $technicianId)
    {
        $records = TimeSpentOnJob::where('job_id', $jobId)
            ->where('technician_id', $technicianId)
            ->get();

        $totalSeconds = 0;

        foreach ($records as $record) {
            $punchIn = Carbon::parse($record->punch_in_at);
            $punchOut = $record->punch_out_at ? Carbon::parse($record->punch_out_at) : now();
            $totalSeconds += $punchIn->diffInSeconds($punchOut);
        }

        return gmdate("H:i:s", $totalSeconds);
    }
}