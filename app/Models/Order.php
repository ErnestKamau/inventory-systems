<?php
// app/Models/Order.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Order extends Model
{
    use HasFactory;

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

    /**
     * Get all items in this order
     *
     * Usage: $order->items
     */
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
     * QUERY SCOPES
     * ======================================== */

    /**
     * Filter pending orders
     *
     * Usage
    * Usage: Order::pending()->get()
     */
    public function scopePending(Builder $query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Filter confirmed orders
     *
     * Usage: Order::confirmed()->get()
     */
    public function scopeConfirmed(Builder $query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Filter cancelled orders
     *
     * Usage: Order::cancelled()->get()
     */
    public function scopeCancelled(Builder $query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Filter orders by payment status
     *
     * Usage: Order::paymentStatus('PAID')->get()
     */
    public function scopePaymentStatus(Builder $query, $status)
    {
        return $query->where('payment_status', $status);
    }

    /**
     * Filter orders by date range
     *
     * Usage: Order::dateRange('2025-01-01', '2025-01-31')->get()
     */
    public function scopeDateRange(Builder $query, $startDate, $endDate)
    {
        return $query->whereBetween('order_date', [$startDate, $endDate]);
    }

    /**
     * Filter today's orders
     *
     * Usage: Order::today()->get()
     */
    public function scopeToday(Builder $query)
    {
        return $query->whereDate('order_date', now()->toDateString());
    }

    /**
     * Filter orders by customer phone
     *
     * Usage: Order::byCustomerPhone('254712345678')->get()
     */
    public function scopeByCustomerPhone(Builder $query, $phone)
    {
        return $query->where('customer_phone', $phone);
    }

    /**
     * Orders that haven't been converted to sales yet
     *
     * Usage: Order::notConverted()->get()
     */
    public function scopeNotConverted(Builder $query)
    {
        return $query->doesntHave('sale');
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

    /* ========================================
     * HELPER METHODS
     * ======================================== */

    /**
     * Mark order as confirmed
     *
     * Usage: $order->markAsConfirmed()
     */
    public function markAsConfirmed()
    {
        $this->update(['status' => 'confirmed']);
    }

    /**
     * Mark order as cancelled
     *
     * Usage: $order->markAsCancelled()
     */
    public function markAsCancelled()
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Mark payment as completed
     *
     * Usage: $order->markAsPaid()
     */
    public function markAsPaid()
    {
        $this->update(['payment_status' => 'PAID']);
    }

    /**
     * Check if order can be converted to sale
     *
     * Business rule: Only confirmed orders without existing sales
     *
     * Usage: $order->canConvertToSale()
     */
    public function canConvertToSale(): bool
    {
        return $this->status === 'confirmed' && !$this->has_sale;
    }

    /**
     * Check if all items are in stock
     *
     * Usage: $order->hasAvailableStock()
     */
    public function hasAvailableStock(): bool
    {
        foreach ($this->items as $item) {
            if (!$item->product->canFulfillOrder($item->quantity)) {
                return false;
            }
        }
        return true;
    }

    public function __toString()
    {
        return "{$this->customer_name} - ({$this->order_time} {$this->order_date})";
    }
}

