<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $table = 'job';

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigner_id');
    }

    public function technicians()
    {
        return $this->hasMany(JobTechnician::class);
    }

    public function materials()
    {
        return $this->hasMany(JobMaterial::class);
    }

    public function expertise()
    {
        return $this->hasMany(JobExpertise::class);
    }

    public function requisitions()
    {
        return $this->hasMany(Requisition::class);
    }
}
