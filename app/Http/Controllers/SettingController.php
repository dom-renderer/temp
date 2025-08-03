<?php

namespace App\Http\Controllers;

use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Escalation;
use App\Models\Country;
use App\Models\Setting;

class SettingController extends Controller
{
    protected $title = 'Settings';
    protected $view = 'settings.';

    public function __construct()
    {
        $this->middleware('permission:settings.index')->only(['index']);
        $this->middleware('permission:settings.update')->only(['update']);

        $this->middleware('permission:job.settings')->only(['jobIndex']);
        $this->middleware('permission:job.settings-update')->only(['jobUpdate']);
    }

    public function index()
    {
        $setting = Setting::first();
        $title = $this->title;
        $subTitle = 'Manage Application Settings';
        $countries = Country::pluck('name', 'id');

        return view($this->view . 'index', compact('title', 'subTitle', 'setting', 'countries'));
    }

    public function update(Request $request)
    {
        $setting = Setting::first();
        $request->validate([
            'name' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,ico|max:2048',
            'favicon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,ico|max:1024',
        ]);

        $data = $request->only(['name', 'theme_color']);

        $destinationPath = public_path('settings-media');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = 'logo.' . $file->getClientOriginalExtension();
            $file->move($destinationPath, $filename);
            $data['logo'] = $filename;
        }
        if ($request->hasFile('favicon')) {
            $file = $request->file('favicon');
            $filename = 'favicon.' . $file->getClientOriginalExtension();
            $file->move($destinationPath, $filename);
            $data['favicon'] = $filename;
        }

        if ($setting) {
            $setting->update($data);
        } else {
            Setting::create($data);
        }

        return redirect()->route('settings.index')->with('success', 'Application settings updated successfully.');
    }

    public function jobIndex()
    {
        $title = 'Job ' . $this->title;
        $subTitle = 'Manage Job Settings';
        
        $escalations = Escalation::orderBy('level', 'asc')->get();

        return view($this->view . 'job-index', compact('title', 'subTitle', 'escalations'));
    }

    public function jobUpdate(Request $request)
    {
        $request->validate([
            'escalations' => 'required|array',
            'escalations.*.time' => 'required|integer|min:1',
            'escalations.*.time_type' => 'required|in:MINUTE,HOUR,DAY',
            'escalations.*.priority' => 'required|in:LOW,MEDIUM,HIGH,CRITICAL',
            'escalations.*.template_id' => 'required|exists:notification_templates,id',
            'escalations.*.departments' => 'nullable|array',
            'escalations.*.departments.*' => 'exists:departments,id',
        ]);

        DB::beginTransaction();

        try {

            $escToKeep = [];

            foreach ($request->escalations as $key => $escalationData) {
                if (isset($escalationData['id']) && $escalationData['id'] > 0) {
                    Escalation::where('id', $escalationData['id'])->update([
                        'level' => $escalationData['level'] ?? ($key + 1),
                        'time' => $escalationData['time'],
                        'time_type' => $escalationData['time_type'],
                        'priority' => $escalationData['priority'],
                        'template_id' => $escalationData['template_id'],
                        'departments' => $escalationData['departments'] ?? [],
                    ]);

                    $escToKeep[] = $escalationData['id'];
                } else {
                    $escToKeep[] = Escalation::create([
                        'level' => $escalationData['level'] ?? ($key + 1),
                        'time' => $escalationData['time'],
                        'time_type' => $escalationData['time_type'],
                        'priority' => $escalationData['priority'],
                        'template_id' => $escalationData['template_id'],
                        'departments' => $escalationData['departments'] ?? [],
                    ])->id;
                }
            }

            if (!empty($escToKeep)) {
                Escalation::whereNotIn('id', $escToKeep)->delete();
            } else {
                Escalation::where('id', '>', 0)->delete();
            }

            DB::commit();
            return redirect()->route('job.settings')->with('success', 'Job escalation settings updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('job.settings')->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }
} 