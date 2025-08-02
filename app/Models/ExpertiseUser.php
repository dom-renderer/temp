<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ExpertiseUser extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function expertise() {
        return $this->belongsTo(Expertise::class, 'expertise_id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id');        
    }
}
