<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class JobMaterial extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
