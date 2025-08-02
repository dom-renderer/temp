<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use SoftDeletes;

    protected $guarded = [];
}
