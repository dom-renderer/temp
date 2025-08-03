<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'type' => 'array'
    ];

    /**
     * Get the notification types as a formatted string
     */
    public function getTypeDisplayAttribute()
    {
        return implode(', ', $this->type);
    }

    /**
     * Get available variables for this template
     */
    public function getAvailableVariablesAttribute()
    {
        return [
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
        ];
    }

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * Scope for inactive templates
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'INACTIVE');
    }
}
