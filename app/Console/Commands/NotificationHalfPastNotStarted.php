<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ChecklistTask;
use App\Models\DeviceToken;
use App\Helpers\Helper;
use App\Models\Store;
use \Carbon\Carbon;

class NotificationHalfPastNotStarted extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'half:past';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $tasks = ChecklistTask::whereBetween('date', [
            Carbon::now()->subDays(3)->startOfDay(),
            Carbon::now()->addDays(3)->endOfDay()
        ])
        ->whereIn('status', [0])
        ->whereHas('parent.parent.checklist.presetemplates')
        ->scheduling()
        ->get();

        foreach ($tasks as $task) {
            $startAt = $task->parent->parent->start_at ?? '';
            $endTime = $task->parent->parent->completed_by ?? '';
            $endGraceTime = $task->parent->parent->end_grace_time ?? '';

            if (!empty($startAt) && !empty($endTime)) {
                $theStartTime = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d', strtotime($task->date)) . ' ' . date('H:i:s', strtotime($startAt)));
                $theEndTime = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d', strtotime($task->date)) . ' ' . date('H:i:s', strtotime($endTime)));

                if (!empty($endGraceTime)) {
                    list($h, $m, $i) = explode(':', $endGraceTime);
                    $theEndTime->addHours($h)->addMinutes($m)->addSeconds($i);
                }

                $halfway = $theStartTime->copy()->addSeconds($theEndTime->diffInSeconds($theStartTime) / 2);
                $halfway = Carbon::createFromFormat('Y-m-d H:i:s', $halfway);

                $now = Carbon::now();
                $windowStart = $halfway->copy()->subMinutes(1);
                $windowEnd = $halfway->copy()->subMinutes(1);
    
                if ($now->between($windowStart, $windowEnd)) {

                    if (isset($task->parent->parent->checklist->presetemplates) && is_iterable($task->parent->parent->checklist->presetemplates)) {
                        foreach ($task->parent->parent->checklist->presetemplates()->with('ntemp')->where('type', 2)->get() as $notification) {
                            if (isset($notification->ntemp->id)) {
                                if ($notification->ntemp->type === 0) {
    
                                    if (isset($task->parent)) {
                                        $extra = $task->parent;
        
                                        $user = \App\Models\User::find($task->parent->user_id);
                                        $location = Store::find($extra->store_id);
                                        $checklist = \App\Models\DynamicForm::find($task->parent->parent->checklist_id);
        
                                        $content = str_replace(array_keys(Helper::$notificationTemplatePlaceholders), [
                                            isset($user->id) ? ("{$user->name} {$user->middle_name} {$user->last_name}") : 'N/A',
                                            $user->username ?? 'N/A',
                                            $user->phone_number ?? 'N/A',
                                            $user->email ?? 'N/A',
                                            $location->name ?? 'N/A',
                                            $checklist->name ?? 'N/A',
                                            'N/A'
                                        ], $notification->ntemp->content);
        
                                        $title = str_replace(array_keys(Helper::$notificationTemplatePlaceholders), [
                                            isset($user->id) ? ("{$user->name} {$user->middle_name} {$user->last_name}") : 'N/A',
                                            $user->username ?? 'N/A',
                                            $user->phone_number ?? 'N/A',
                                            $user->email ?? 'N/A',
                                            $location->name ?? 'N/A',
                                            $checklist->name ?? 'N/A',
                                            'N/A'
                                        ], $notification->ntemp->title);
        
                                        \Illuminate\Support\Facades\Mail::to($user->email)
                                        ->send(new \App\Mail\EscalationMail($title, $content));
                                    }
        
                                } else if ($notification->ntemp->type == 1) {
        
                                    if (isset($task->parent)) {
                                        $extra = $task->parent;
        
                                        $user = \App\Models\User::find($task->parent->user_id);
                                        $location = Store::find($extra->store_id);
                                        $checklist = \App\Models\DynamicForm::find($task->parent->parent->checklist_id);
        
                                        $content = str_replace(array_keys(Helper::$notificationTemplatePlaceholders), [
                                            isset($user->id) ? ("{$user->name} {$user->middle_name} {$user->last_name}") : 'N/A',
                                            $user->username ?? 'N/A',
                                            $user->phone_number ?? 'N/A',
                                            $user->email ?? 'N/A',
                                            $location->name ?? 'N/A',
                                            $checklist->name ?? 'N/A',
                                            'N/A'
                                        ], $notification->ntemp->content);
        
                                        $title = str_replace(array_keys(Helper::$notificationTemplatePlaceholders), [
                                            isset($user->id) ? ("{$user->name} {$user->middle_name} {$user->last_name}") : 'N/A',
                                            $user->username ?? 'N/A',
                                            $user->phone_number ?? 'N/A',
                                            $user->email ?? 'N/A',
                                            $location->name ?? 'N/A',
                                            $checklist->name ?? 'N/A',
                                            'N/A'
                                        ], $notification->ntemp->title);
        
                                        $deviceTokens = [];
                                        if (isset($user->id)) {
                                            $deviceTokens = DeviceToken::where('user_id', $user->id)->pluck('token')->toArray();
                                        }
                        
                                        if (!empty($deviceTokens)) {
                                            Helper::sendPushNotification($deviceTokens, [
                                                'title' => $title,
                                                'description' => $content
                                            ]);
                                        }
                                    }
        
                                }
                            }
                        }
                    }

                }
            }
        }
    }
}
