<?php
// app/Models/Order.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Traits\OrderScopes;

class Order extends Model
{
    use HasFactory, OrderScopes;

    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'customer_name',
        'customer_phone',
        'notes',
        'status',
        'payment_status',
        'order_date',
        'order_time',
    ];

    protected $casts = [
        'order_date' => 'date',
        'order_time' => 'datetime:H:i:s', // Cast as time only
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'total_amount',
    ];

    /* ========================================
     * RELATIONSHIPS
     * ======================================== */

    /**
     * Get the user who placed this order (nullable)
     *
     * Usage: $order->user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the sale created from this order (One-to-One)
     *
     * Usage: $order->sale
     */
    public function sale()
    {
        return $this->hasOne(Sale::class);
    }

    /* ========================================
     * ACCESSORS (Computed Properties)
     * ======================================== */

    /**
     * Calculate total order amount from items
     *
     * CRITICAL: This uses eager loaded data if available,
     * otherwise makes a database query
     *
     * Usage: $order->total_amount
     */
    public function getTotalAmountAttribute()
    {
        // If items already loaded (eager loading), use them
        if ($this->relationLoaded('items')) {
            return $this->items->sum(function ($item) {
                return $item->subtotal;
            });
        }
        
        // Otherwise, query database
        return $this->items()->get()->sum(function ($item) {
            return $item->subtotal;
        });
    }

    /**
     * Check if order is pending
     *
     * Usage: $order->is_pending
     */
    public function getIsPendingAttribute()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if order is confirmed
     *
     * Usage: $order->is_confirmed
     */
    public function getIsConfirmedAttribute()
    {
        return $this->status === 'confirmed';
    }

    /**
     * Check if order is cancelled
     *
     * Usage: $order->is_cancelled
     */
    public function getIsCancelledAttribute()
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if order has been paid
     *
     * Usage: $order->is_paid
     */
    public function getIsPaidAttribute()
    {
        return $this->payment_status === 'PAID';
    }

    /**
     * Check if order has been converted to sale
     *
     * Usage: $order->has_sale
     */
    public function getHasSaleAttribute()
    {
        return $this->sale()->exists();
    }

    /**
     * Get order age in days
     *
     * Usage: $order->age_in_days
     */
    public function getAgeInDaysAttribute()
    {
        return now()->diffInDays($this->created_at);
    }

    public function __toString()
    {
        return "{$this->customer_name} - ({$this->order_time} {$this->order_date})";
    }
}

