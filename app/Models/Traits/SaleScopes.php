<?php
// app/Models/Traits/SaleScopes.php

namespace App\Models\Traits;
use Illuminate\Database\Eloquent\Builder;

trait SaleScopes
{
    /**
     * Filter fully paid sales
     *
     * Usage: Sale::fullyPaid()->get()
     */
    public function scopeFullyPaid(Builder $query)
    {
        return $query->where('payment_status', 'fully-paid');
    }

    /**
     * Filter partially paid sales
     *
     * Usage: Sale::partial()->get()
     */
    public function scopePartial(Builder $query)
    {
        return $query->where('payment_status', 'partial');
    }

    /**
     * Filter unpaid sales
     *
     * Usage: Sale::unpaid()->get()
     */
    public function scopeUnpaid(Builder $query)
    {
        return $query->where('payment_status', 'no-payment');
    }

    /**
     * Filter overdue sales
     *
     * Usage: Sale::overdue()->get()
     */
    public function scopeOverdue(Builder $query)
    {
        return $query->where('payment_status', 'overdue');
    }

    /**
     * Sales with outstanding balance (partial + unpaid + overdue)
     *
     * Usage: Sale::withBalance()->get()
     */
    public function scopeWithBalance(Builder $query)
    {
        return $query->whereIn('payment_status', ['partial', 'no-payment', 'overdue']);
    }

    /**
     * Filter by date range
     *
     * Usage: Sale::dateRange('2025-01-01', '2025-01-31')->get()
     */
    public function scopeDateRange(Builder $query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Today's sales
     *
     * Usage: Sale::today()->get()
     */
    public function scopeToday(Builder $query)
    {
        return $query->whereDate('created_at', now()->toDateString());
    }

    /**
     * This week's sales
     *
     * Usage: Sale::thisWeek()->get()
     */
    public function scopeThisWeek(Builder $query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * This month's sales
     *
     * Usage: Sale::thisMonth()->get()
     */
    public function scopeThisMonth(Builder $query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    /**
     * Sales by customer phone
     *
     * Usage: Sale::byCustomerPhone('254712345678')->get()
     */
    public function scopeByCustomerPhone(Builder $query, $phone)
    {
        return $query->where('customer_phone', $phone);
    }

    /**
     * Sales nearing due date (within 2 days)
     *
     * Usage: Sale::nearDue()->get()
     */
    public function scopeNearDue(Builder $query)
    {
        return $query->whereIn('payment_status', ['partial', 'no-payment'])
                     ->whereNotNull('due_date')
                     ->whereBetween('due_date', [
                         now(),
                         now()->addDays(2)
                     ]);
    }
}
