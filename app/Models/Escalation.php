<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Escalation extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'recipients' => 'array',
        'departments' => 'array',
    ];

    /**
     * Get the notification template for this escalation
     */
    public function template()
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }

    /**
     * Get the priority display name
     */
    public function getPriorityDisplayAttribute()
    {
        return ucfirst(strtolower($this->priority));
    }

    /**
     * Get the time type display name
     */
    public function getTimeTypeDisplayAttribute()
    {
        return ucfirst(strtolower($this->time_type));
    }

    /**
     * Scope for active escalations
     */
    public function scopeActive($query)
    {
        return $query->where('level', '>', 0);
    }

    /**
     * Get all escalations ordered by level
     */
    public static function getEscalationLevels()
    {
        return self::orderBy('level', 'asc')->get();
    }
}
