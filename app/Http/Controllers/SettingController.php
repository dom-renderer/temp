<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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


        return view($this->view . 'job-index', compact('title', 'subTitle'));
    }

    public function jobUpdate(Request $request)
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
} 