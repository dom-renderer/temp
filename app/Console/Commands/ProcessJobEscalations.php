<?php

namespace App\Console\Commands;

use App\Models\Department;
use Illuminate\Console\Command;

class ProcessJobEscalations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-job-escalations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now();

        $jobs = \App\Models\Job::where('status', 'PENDING')
            ->whereNotNull('visiting_date')
            ->where('visiting_date', '<=', $now)
            ->get();

        $escalations = \App\Models\Escalation::whereNull('deleted_at')->orderBy('level')->get();

        foreach ($jobs as $job) {
            foreach ($escalations as $escalation) {
                $delay = match ($escalation->time_type) {
                    'MINUTE' => $escalation->time,
                    'HOUR'   => $escalation->time * 60,
                    'DAY'    => $escalation->time * 60 * 24,
                    default  => 0
                };

                $escalationTime = \Carbon\Carbon::parse($job->visiting_date)->addMinutes($delay);

                $alreadyEscalated = \DB::table('job_escalation_logs')
                    ->where('job_id', $job->id)
                    ->where('escalation_id', $escalation->id)
                    ->exists();

                if ($now->greaterThanOrEqualTo($escalationTime) && !$alreadyEscalated) {
                    $this->sendNotification($job, $escalation);

                    \DB::table('job_escalation_logs')->insert([
                        'job_id' => $job->id,
                        'escalation_id' => $escalation->id,
                        'level' => $escalation->level,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    private function sendNotification($job, $escalation)
    {
        $template = \App\Models\NotificationTemplate::where('id', $escalation->template_id)
            ->where('status', 'ACTIVE')
            ->first();

        if (!$template) {
            return;
        }

        $title = $escalation->title;
        $body = $escalation->body;

        if (is_array($template->type) && !empty($template->type)) {
                $departments = json_decode($escalation->departments ?? '[]', true);

                foreach ($departments as $department) {
                    $userIds = \App\Models\DepartmentUser::where('department_id', $department)->pluck('user_id');
                    $departmentEmails = \App\Models\User::whereIn('id', $userIds)->get();
                    $dept = Department::find($department);

                    foreach ($departmentEmails as $theUser) {

                        $exps = $technicianNames = [];

                        if (isset($job->technicians)) {
                            foreach ($job->technicians as $jt) {
                                $technicianNames[] = $jt->technician->name ?? '';
                            }
                        }

                        if (isset($job->expertise)) {
                            foreach ($job->expertise as $je) {
                                $exps[] = $je->expertise->name ?? '';
                            }
                        }

                        $title = str_replace([
                            '{user_name}',
                            '{user_email}',
                            '{job_title}',
                            '{job_code}',
                            '{job_status}',
                            '{customer_name}',
                            '{customer_email}',
                            '{technician_name}',
                            '{department_name}',
                            '{expertise_name}',
                            '{company_name}',
                            '{current_date}',
                            '{current_time}'
                        ], [
                            $theUser->name,
                            $theUser->email,
                            $job->title,
                            $job->code,
                            $job->status,
                            $job->email,
                            implode(', ', $technicianNames),
                            $dept->name ?? '',
                            implode(', ', $exps),
                            \App\Helpers\Helper::title(),
                            date('d-m-Y'),
                            date('H:i')

                        ], $title);

                        $body = str_replace([
                            '{user_name}',
                            '{user_email}',
                            '{job_title}',
                            '{job_code}',
                            '{job_status}',
                            '{customer_name}',
                            '{customer_email}',
                            '{technician_name}',
                            '{department_name}',
                            '{expertise_name}',
                            '{company_name}',
                            '{current_date}',
                            '{current_time}'
                        ], [
                            $theUser->name,
                            $theUser->email,
                            $job->title,
                            $job->code,
                            $job->status,
                            $job->email,
                            implode(', ', $technicianNames),
                            $dept->name ?? '',
                            implode(', ', $exps),
                            \App\Helpers\Helper::title(),
                            date('d-m-Y'),
                            date('H:i')
                        ], $body);                        

                        try {
                            if (in_array('email', $template->type)) {
                                \Illuminate\Support\Facades\Mail::to($theUser->email)->send(new \App\Mail\JobEscalationMail($title, $body));
                            }
                        } catch (\Exception $e) {
                            \Log::warning("Escalation mail failed for {$theUser->email} : id{$theUser->id}: " . $e->getMessage());
                            continue;
                        }

                        try {
                            if (in_array('push-notification', $template->type)) {
                                \App\Helpers\Helper::sendPushNotification(\App\Models\DeviceToken::where('user_id', $theUser->id)->pluck('token')->toArray(), $title, $body);
                            }
                        } catch (\Exception $e) {
                            \Log::warning("Escalation mail failed for {$theUser->email} : id{$theUser->id}: " . $e->getMessage());
                            continue;
                        }

                        try {
                            if (in_array('system', $template->type)) {
                                \Illuminate\Support\Facades\Mail::to($theUser->email)->send(new \App\Mail\JobEscalationMail($title, $body));
                            }
                        } catch (\Exception $e) {
                            \Log::warning("Escalation mail failed for {$theUser->email} : id{$theUser->id}: " . $e->getMessage());
                            continue;
                        }
                    }
                }
        }

    }


}
