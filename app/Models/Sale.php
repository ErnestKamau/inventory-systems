<?php
// app/Models/Sale.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Traits\SaleScopes;

class Sale extends Model
{
    use HasFactory, SaleScopes;

    protected $table = 'sales';

    protected $fillable = [
        'order_id',
        'sale_number',
        'customer_name',
        'customer_phone',
        'total_amount',
        'cost_amount',
        'profit_amount',
        'payment_status',
        'due_date',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'cost_amount' => 'decimal:2',
        'profit_amount' => 'decimal:2',
        'due_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'total_paid',
        'balance',
        'is_fully_paid',
    ];

    /* ========================================
     * RELATIONSHIPS
     * ======================================== */

    /**
     * Get the order this sale was created from (One-to-One)
     *
     * Usage: $sale->order
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get all items in this sale
     *
     * Usage: $sale->items
     */
    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Get all payments for this sale
     *
     * Usage: $sale->payments
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /* ========================================
     * ACCESSORS (Computed Properties)
     * ======================================== */

    /**
     * Calculate total amount paid for this sale
     *
     * Usage: $sale->total_paid
     */
    public function getTotalPaidAttribute()
    {
        // If payments already loaded, use them
        if ($this->relationLoaded('payments')) {
            return $this->payments->sum('amount');
        }
        
        // Otherwise query database
        return $this->payments()->sum('amount') ?? 0;
    }

    /**
     * Calculate remaining balance
     *
     * Usage: $sale->balance
     */
    public function getBalanceAttribute()
    {
        return $this->total_amount - $this->total_paid;
    }

    /**
     * Check if sale is fully paid
     *
     * Returns true if balance is zero or negative (overpaid)
     *
     * Usage: $sale->is_fully_paid
     */
    public function getIsFullyPaidAttribute()
    {
        return $this->balance <= 0;
    }

    /**
     * Calculate profit percentage
     *
     * Formula: (profit / cost) * 100
     *
     * Usage: $sale->profit_percentage
     */
    public function getProfitPercentageAttribute()
    {
        if ($this->cost_amount > 0) {
            return ($this->profit_amount / $this->cost_amount) * 100;
        }
        return 0;
    }

    /**
     * Check if sale has any payments
     *
     * Usage: $sale->has_payments
     */
    public function getHasPaymentsAttribute()
    {
        return $this->payments()->exists();
    }

    /**
     * Get payment progress percentage
     *
     * Usage: $sale->payment_progress
     * Returns: 0-100
     */
    public function getPaymentProgressAttribute()
    {
        if ($this->total_amount > 0) {
            return min(100, ($this->total_paid / $this->total_amount) * 100);
        }
        return 0;
    }

    public function __toString()
    {
        return "{$this->sale_number} - {$this->customer_name} - {$this->total_amount}";
    }
}



