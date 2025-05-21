<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\DeviceToken;
use App\Helpers\Helper;
use App\Models\Store;

class NotificationRescheduleRejection implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $task;
    public $date;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($task, $date)
    {
        $this->task = $task;
        $this->date = $date;
    }

    public function handle()
    {
        $task = $this->task;
        $date = $this->date;

        if (isset($task->parent->parent->checklist->presetemplates) && is_iterable($task->parent->parent->checklist->presetemplates)) {
            foreach ($task->parent->parent->checklist->presetemplates()->with('ntemp')->where('type', 6)->get() as $notification) {
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
