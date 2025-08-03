<?php

namespace App\Helpers;

use \Illuminate\Support\Facades\DB;
use App\Models\TimeSpentOnJob;
use Illuminate\Http\Request;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\User;
use Carbon\Carbon;

class Helper {
    
    public static $defaulDialCode = 'bb';

    public static function title ($title = '') {
        if (!empty($title)) {
            return $title;
        } else if ($name = DB::table('settings')->first()?->name) {
            return $name;
        } else {
            return env('APP_NAME', '');
        }
    }

    public static function logo () {
        if ($name = DB::table('settings')->first()?->logo) {
            return url("settings-media/{$name}");
        } else {
            return url('assets/images/logo.png');
        }
    }

    public static function favicon () {
        if ($name = DB::table('settings')->first()?->favicon) {
            return url("settings-media/{$name}");
        } else {
            return url('assets/images/favicon.ico');
        }
    }

    public static function bgcolor ($bg = null) {
        if (!empty($bg)) {
            return $bg;
        } else if ($color = DB::table('settings')->first()?->theme_color) {
            return $color;
        } else {
            return '#3a082f';
        }
    }

    public function getCountries(Request $request)
    {
        $queryString = trim($request->searchQuery);
        $page = $request->input('page', 1);
        $limit = 10;
    
        $query = Country::query();
    
        if (!empty($queryString)) {
            $query->where('name', 'LIKE', "%{$queryString}%");
        }
    
        $data = $query->paginate($limit, ['*'], 'page', $page);
        $response = $data->map(function ($item) {
            return [
                'id' => $item->id,
                'text' => $item->name
            ];
        });

        return response()->json([
            'items' => $response->reverse()->values(),
            'pagination' => [
                'more' => $data->hasMorePages()
            ]
        ]);
    }

    public function getStatesByCountry(Request $request)
    {
        $queryString = trim($request->searchQuery);
        $page = $request->input('page', 1);
        $limit = 10;
    
        $query = State::query()
        ->where('country_id', request('country_id'));
    
        if (!empty($queryString)) {
            $query->where('name', 'LIKE', "%{$queryString}%");
        }
    
        $data = $query->paginate($limit, ['*'], 'page', $page);
        $response = $data->map(function ($item) {
            return [
                'id' => $item->id,
                'text' => $item->name
            ];
        });

        return response()->json([
            'items' => $response->reverse()->values(),
            'pagination' => [
                'more' => $data->hasMorePages()
            ]
        ]);
    }

    public function getCitiesByState(Request $request)
    {
        $queryString = trim($request->searchQuery);
        $page = $request->input('page', 1);
        $limit = 10;
    
        $query = City::query()
        ->where('state_id', $request->state_id);
    
        if (!empty($queryString)) {
            $query->where('name', 'LIKE', "%{$queryString}%");
        }
    
        $data = $query->paginate($limit, ['*'], 'page', $page);
        $response = $data->map(function ($item) {
            return [
                'id' => $item->id,
                'text' => $item->name
            ];
        });

        return response()->json([
            'items' => $response->reverse()->values(),
            'pagination' => [
                'more' => $data->hasMorePages()
            ]
        ]);
    }

    public function getUsers(Request $request)
    {
        $queryString = trim($request->searchQuery);
        $roles = $request->input('roles', null);
        $expertises = $request->input('expertises', null);
        $departments = $request->input('departments', null);
        $page = $request->input('page', 1);
        $addNewOption = $request->input('addNewOption', 0);
        $includeUserData = $request->input('includeUserData', false);
        $limit = 10;
    
        $query = User::query();
    
        if (!empty($queryString)) {
            $query->where('name', 'LIKE', "%{$queryString}%");
        }

        if (!empty($roles)) {
            $roles = is_string($roles) ? explode(',', $roles) : (is_array($roles) ? $roles : []);
            $query->whereHas('roles', fn  ($builder) => $builder->whereIn('name', $roles));
        }

        if (!empty($expertises)) {
            $expertises = is_string($expertises) ? explode(',', $expertises) : (is_array($expertises) ? $expertises : []);
            $query->whereHas('expertise', fn  ($builder) => $builder->whereIn('expertise_id', $expertises));
        }
        
        if (!empty($departments)) {
            $departments = is_string($departments) ? explode(',', $departments) : (is_array($departments) ? $departments : []);
            $query->whereHas('department', fn  ($builder) => $builder->whereIn('department_id', $departments));
        }

        $data = $query->orderBy('name', 'ASC')->paginate($limit, ['*'], 'page', $page);
        $response = $data->map(function ($item) use ($includeUserData) {
            $result = [
                'id' => $item->id,
                'text' => $item->name
            ];
            
            if ($includeUserData) {
                $result['user'] = $item;
                $result['alternate_dial_code_iso'] = Helper::getIso2ByDialCode($item->alternate_dial_code);
            }
            
            return $result;
        });

        if ($addNewOption === '1' && $page == 1) {
            if ($response->count() > 0) {
                $response->push([
                    'id' => 'ADD_NEW_USER',
                    'text' => 'Add Customer'
                ])->unique();
            } else {
                $response->push([
                    'id' => 'ADD_NEW_USER',
                    'text' => 'Add Customer'
                ]);
            }
        }

        return response()->json([
            'items' => $response->values(),
            'pagination' => [
                'more' => $data->hasMorePages()
            ]
        ]);
    }

    public function getCategories(Request $request)
    {
        $queryString = trim($request->searchQuery);
        $page = $request->input('page', 1);
        $except = $request->input('except', null);
        $limit = 10;

        $query = \App\Models\Category::query();
        if (!empty($queryString)) {
            $query->where('name', 'LIKE', "%{$queryString}%");
        }

        if (!empty($except)) {
            $query = $query->where('id', '!=', $except);
        }

        $data = $query->paginate($limit, ['*'], 'page', $page);
        $response = $data->map(function ($item) {
            return [
                'id' => $item->id,
                'text' => $item->name
            ];
        });
        return response()->json([
            'items' => $response->values(),
            'pagination' => [
                'more' => $data->hasMorePages()
            ]
        ]);
    }

    public function getProducts(Request $request)
    {
        $queryString = trim($request->searchQuery);
        $page = $request->input('page', 1);
        $except = $request->input('except', null);
        $category = $request->input('category', null);
        $categoryId = $request->input('category_id', null);
        $limit = 10;

        $query = \App\Models\Product::query();
        if (!empty($queryString)) {
            $query->where('name', 'LIKE', "%{$queryString}%");
        }

        if (!empty($except)) {
            $query = $query->where('id', '!=', $except);
        }

        if ($request->has('category')) {
            $query = $query->where('category_id', $category);
        }

        if ($request->has('category_id') && !empty($categoryId)) {
            $query = $query->where('category_id', $categoryId);
        }

        $data = $query->paginate($limit, ['*'], 'page', $page);
        $response = $data->map(function ($item) {
            return [
                'id' => $item->id,
                'text' => $item->name . ' - ' . $item->sku,
                'sku' => $item->sku,
                'price' => $item->amount
            ];
        });
        return response()->json([
            'items' => $response->values(),
            'pagination' => [
                'more' => $data->hasMorePages()
            ]
        ]);
    }

    public function getProductCategories(Request $request)
    {
        $queryString = trim($request->searchQuery);
        $page = $request->input('page', 1);
        $limit = 10;

        $query = \App\Models\Category::query();
        if (!empty($queryString)) {
            $query->where('name', 'LIKE', "%{$queryString}%");
        }
        $data = $query->paginate($limit, ['*'], 'page', $page);
        $response = $data->map(function ($item) {
            return [
                'id' => $item->id,
                'text' => $item->name
            ];
        });
        return response()->json([
            'items' => $response->values(),
            'pagination' => [
                'more' => $data->hasMorePages()
            ]
        ]);
    }

    public function getDepartments(Request $request)
    {
        $queryString = trim($request->searchQuery);
        $page = $request->input('page', 1);
        $limit = 10;

        $query = \App\Models\Department::query();
        
        if (!empty($queryString)) {
            $query->where('name', 'LIKE', "%{$queryString}%");
        }

        $data = $query->paginate($limit, ['*'], 'page', $page);
        $response = $data->map(function ($item) {
            return [
                'id' => $item->id,
                'text' => $item->name
            ];
        });
        return response()->json([
            'items' => $response->values(),
            'pagination' => [
                'more' => $data->hasMorePages()
            ]
        ]);
    }

    public function getExpertise(Request $request)
    {
        $queryString = trim($request->searchQuery);
        $page = $request->input('page', 1);
        $limit = 10;

        $query = \App\Models\Expertise::query();

        if (!empty($queryString)) {
            $query->where('name', 'LIKE', "%{$queryString}%");
        }

        $data = $query->paginate($limit, ['*'], 'page', $page);
        $response = $data->map(function ($item) {
            return [
                'id' => $item->id,
                'text' => $item->name
            ];
        });
        return response()->json([
            'items' => $response->values(),
            'pagination' => [
                'more' => $data->hasMorePages()
            ]
        ]);
    }

    public function getJobs(Request $request)
    {
        $queryString = trim($request->searchQuery);
        $page = $request->input('page', 1);
        $limit = 10;

        $query = \App\Models\Job::query();

        if (!empty($queryString)) {
            $query->where('code', 'LIKE', "%{$queryString}%");
        }

        $data = $query->paginate($limit, ['*'], 'page', $page);
        $response = $data->map(function ($item) {
            return [
                'id' => $item->id,
                'text' => $item->code
            ];
        });
        return response()->json([
            'items' => $response->values(),
            'pagination' => [
                'more' => $data->hasMorePages()
            ]
        ]);
    }

    public function notificationTemplates(Request $request) {
        $queryString = trim($request->searchQuery);
        $page = $request->input('page', 1);
        $limit = 10;

        $query = \App\Models\NotificationTemplate::active();

        if (!empty($queryString)) {
            $query->where('title', 'LIKE', "%{$queryString}%");
        }

        $data = $query->paginate($limit, ['*'], 'page', $page);
        $response = $data->map(function ($item) {
            return [
                'id' => $item->id,
                'text' => $item->title . ' - ' . str_replace(',', ' | ', ucwords($item->type_display))
            ];
        });
        return response()->json([
            'items' => $response->values(),
            'pagination' => [
                'more' => $data->hasMorePages()
            ]
        ]);
    }

    public static function getIso2ByDialCode($dialCode = null) {
        if (empty(trim($dialCode))) {
            $dialCode = '91';
        }

        $dialCode = trim(str_replace('+', '', $dialCode));
        return strtolower(Country::select('iso2')->where('phonecode', "+{$dialCode}")->orWhere('phonecode', $dialCode)->first()->iso2 ?? 'in');
    }

    public static function jobCode() {
        $orderNo = 0;
        
        if (\App\Models\Job::withTrashed()->orderBy('id', 'DESC')->first() !== null) {
            $orderNo = \App\Models\Job::withTrashed()->orderBy('id', 'DESC')->first()->id;
        }

        $orderNo += 1;
        $orderNo = sprintf('%07d', $orderNo);
        $orderNo = "JOB-{$orderNo}";

        return $orderNo;
    }

    public static function requisitionCode() {
        $orderNo = 0;
        
        if (\App\Models\Requisition::withTrashed()->orderBy('id', 'DESC')->first() !== null) {
            $orderNo = \App\Models\Requisition::withTrashed()->orderBy('id', 'DESC')->first()->id;
        }

        $orderNo += 1;
        $orderNo = sprintf('%07d', $orderNo);
        $orderNo = "REQ-{$orderNo}";

        return $orderNo;
    }

    public static function activateNavigation (...$routes) {
        if (!empty($routes)) {
            return in_array(Request::route()->getName(), $routes) ? 'active' : '';
        }

        return '';
    }

    public static function calculateTotalTimePerEmployee($jobId)
    {
        $records = TimeSpentOnJob::with('user')
            ->where('job_id', $jobId)
            ->get()
            ->groupBy('technician_id');

        $result = [];

        foreach ($records as $technicianId => $punches) {
            $totalSeconds = 0;

            foreach ($punches as $punch) {
                $punchIn = Carbon::parse($punch->punch_in_at);
                $punchOut = $punch->punch_out_at ? Carbon::parse($punch->punch_out_at) : now();
                $totalSeconds += $punchIn->diffInSeconds($punchOut);
            }

            $result[$technicianId] = [
                'technician_id' => $technicianId,
                'technician_name' => $punch->user->name ?? '',
                'technician_email' => $punch->user->email ?? '',
                'total_time_spent' => self::formatTime($totalSeconds),
            ];
        }

        return $result;
    }

    private static function formatTime($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%02dh %02dm %02ds', $hours, $minutes, $remainingSeconds);
    }

    public static function sendPushNotification($tokens = [], $title = '', $body = '') {

    }
}