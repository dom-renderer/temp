<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class JobExpertise extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function expertise()
    {
        return $this->belongsTo(Expertise::class);
    }
}
