<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class ChecklistScheduling extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $auditInclude = [
        'checklist_id',
        'weekdays',
        'weekday_time',
        'frequency_type',
        'perpetual',
        'start',
        'end',
        'checker_branch_type',
        'checker_branch_id',
        'checker_user_id',
        'start_at',
        'completed_by',
        'do_not_allow_late_submission',
        'hours_required',
        'start_grace_time',
        'end_grace_time',
        'allow_rescheduling'
    ];

    protected $casts = [
        'completion_data' => 'object'
    ];

    protected $guarded = [];

    public function checklist()
    {
        return $this->belongsTo(DynamicForm::class, 'checklist_id');
    }

    public function checker() {
        return $this->belongsTo(User::class, 'checker_user_id');
    }

    public function children() {
        return $this->hasMany(ChecklistSchedulingExtra::class, 'checklist_scheduling_id');
    }
}
