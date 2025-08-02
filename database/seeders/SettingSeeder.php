<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Setting::doesntExist()) {
            Setting::create([
                'name' => 'DMS',
                'logo' => 'logo.png',
                'theme_color' => '#00293e',
                'favicon' => 'favicon.ico'
            ]);
        }
    }
}
