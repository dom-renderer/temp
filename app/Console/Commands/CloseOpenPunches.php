<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TimeSpentOnJob;
use Carbon\Carbon;

class CloseOpenPunches extends Command
{
    protected $signature = 'punch:close-open';
    protected $description = 'Auto punch out technicians who forgot to punch out by setting punch_out_at to 23:59:59';

    public function handle()
    {
        $today = Carbon::today();
        $endOfDay = $today->copy()->endOfDay();

        $openPunches = TimeSpentOnJob::whereDate('date', $today)
            ->whereNull('punch_out_at')
            ->get();

        if ($openPunches->isEmpty()) {
            $this->info('No open punches found.');
            return Command::SUCCESS;
        }

        foreach ($openPunches as $punch) {
            $punch->update([
                'punch_out_at' => $endOfDay,
                'status' => 'PUNCHED_OUT',
            ]);
        }

        $this->info(count($openPunches) . ' open punches updated successfully.');
        return Command::SUCCESS;
    }
}