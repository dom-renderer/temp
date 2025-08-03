<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class JobInspectionOrder extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    /**
     * Get the department for this inspection order
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Scope for ordered inspection orders
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('ordering', 'asc');
    }

    /**
     * Get all inspection orders with departments
     */
    public static function getInspectionOrders()
    {
        return self::with('department')->ordered()->get();
    }

    /**
     * Get the next ordering number
     */
    public static function getNextOrdering()
    {
        $lastOrder = self::max('ordering');
        return $lastOrder ? $lastOrder + 1 : 1;
    }

    /**
     * Reorder inspection orders after deletion
     */
    public static function reorderAfterDeletion()
    {
        $orders = self::ordered()->get();
        foreach ($orders as $index => $order) {
            $order->update(['ordering' => $index + 1]);
        }
    }
}
