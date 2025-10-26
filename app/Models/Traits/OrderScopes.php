<?php

// app/Models/Traits/OrderScopes.php
namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait OrderScopes
{
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
}

