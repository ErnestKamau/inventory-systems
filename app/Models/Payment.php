<?php
// app/Models/Payment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $fillable = [
        'sale_id',
        'method',
        'amount',
        'reference',
        'notes',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /* ========================================
     * RELATIONSHIPS
     * ======================================== */

    /**
     * Get the sale this payment belongs to
     *
     * Usage: $payment->sale
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /* ========================================
     * QUERY SCOPES
     * ======================================== */

    /**
     * Filter by payment method
     *
     * Usage: Payment::byMethod('mpesa')->get()
     */
    public function scopeByMethod(Builder $query, $method)
    {
        return $query->where('method', $method);
    }

    /**
     * Filter cash payments
     *
     * Usage: Payment::cash()->get()
     */
    public function scopeCash(Builder $query)
    {
        return $query->where('method', 'cash');
    }

    /**
     * Filter M-Pesa payments
     *
     * Usage: Payment::mpesa()->get()
     */
    public function scopeMpesa(Builder $query)
    {
        return $query->where('method', 'mpesa');
    }

    /**
     * Filter by date range
     *
     * Usage: Payment::dateRange('2025-01-01', '2025-01-31')->get()
     */
    public function scopeDateRange(Builder $query, $startDate, $endDate)
    {
        return $query->whereBetween('paid_at', [$startDate, $endDate]);
    }

    /**
     * Today's payments
     *
     * Usage: Payment::today()->get()
     */
    public function scopeToday(Builder $query)
    {
        return $query->whereDate('paid_at', now()->toDateString());
    }

    /* ========================================
     * ACCESSORS
     * ======================================== */

    /**
     * Get formatted payment method
     *
     * Usage: $payment->method_label
     */
    public function getMethodLabelAttribute()
    {
        $methods = [
            'cash' => 'Cash',
            'mpesa' => 'M-Pesa',
            'bank_transfer' => 'Bank Transfer',
            'card' => 'Card',
        ];
        
        return $methods[$this->method] ?? $this->method;
    }

    /* ========================================
     * MODEL EVENTS (Automatic Actions)
     * ======================================== */

    /**
     * Boot method - called when model loads
     *
     * This automatically updates the sale's payment status
     * whenever a payment is created or deleted
     */
    protected static function booted()
    {
        // After creating a payment
        static::created(function ($payment) {
            $payment->sale->updatePaymentStatus();
        });

        // After deleting a payment
        static::deleted(function ($payment) {
            $payment->sale->updatePaymentStatus();
        });
    }

    public function __toString()
    {
        return "{$this->method} - {$this->amount}";
    }
}

