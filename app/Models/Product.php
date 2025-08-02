<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];

    public function category()
    {
        return $this->belongsTo(\App\Models\Category::class);
    }

    public function media()
    {
        return $this->hasMany(\App\Models\ProductMedia::class);
    }

    public function requisitions()
    {
        return $this->hasMany(Requisition::class);
    }
}
