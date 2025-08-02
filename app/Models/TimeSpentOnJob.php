<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class TimeSpentOnJob extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function job () {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function user () {
        return $this->belongsTo(User::class, 'technician_id');
    }
}
