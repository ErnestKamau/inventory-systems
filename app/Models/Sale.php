<?php
// app/Models/Sale.php

namespace App\Models;

use App\Models\Traits\SaleScopes;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Sale
 *
 * REFACTORED VERSION - Now follows Single Responsibility Principle
 *
 * Responsibilities:
 * - Define database structure (fillable, casts)
 * - Define relationships
 * - Define computed properties (accessors)
 *
 * Moved Out:
 * - Query Scopes → SaleScopes trait (11 methods)
 * - Business Logic → SalePaymentService (7 methods)
 * - Number Generation → SaleNumberGenerator (1 method)
 * - Status Logic → PaymentStatus enum
 *
 * Before: 27 methods
 * After: 8 methods ✓
 *
 * @package App\Models
 */
class Sale extends Model
{
    use HasFactory, SaleScopes;

    // ============================================
    // CONSTANTS - Fix for SonarQube S1192
    // ============================================
    
    /**
     * Decimal precision for currency fields
     * Using constant instead of duplicating 'decimal:2'
     */
    const DECIMAL_PRECISION = 'decimal:2';

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
        'total_amount' => self::DECIMAL_PRECISION,      // Using constant
        'cost_amount' => self::DECIMAL_PRECISION,       // Using constant
        'profit_amount' => self::DECIMAL_PRECISION,     // Using constant
        'due_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'payment_status' => PaymentStatus::class,       // Cast to enum
    ];

    protected $appends = [
        'total_paid',
        'balance',
        'is_fully_paid',
    ];

    // ============================================
    // RELATIONSHIPS (3 methods)
    // ============================================

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

    // ============================================
    // ACCESSORS - Computed Properties (4 methods)
    // ============================================

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

    // ============================================
    // UTILITY METHOD (1 method)
    // ============================================

    public function __toString()
    {
        return "{$this->sale_number} - {$this->customer_name} - {$this->total_amount}";
    }
}

/**
 * ============================================
 * USAGE EXAMPLES WITH NEW ARCHITECTURE
 * ============================================
 */

// BEFORE (All in model):
// $sale->updatePaymentStatus();
// $sale->setAsDebt(7);
// $sale->addPayment($data);
// $saleNumber = Sale::generateSaleNumber();

// AFTER (Separated):
// $paymentService->updatePaymentStatus($sale);
// $paymentService->setAsDebt($sale, 7);
// $paymentService->addPayment($sale, $data);
// $saleNumber = $numberGenerator->generate();

/**
 * Query Scopes still work the same way:
 *
 * Sale::fullyPaid()->get()
 * Sale::overdue()->get()
 * Sale::today()->get()
 * Sale::withBalance()->get()
 *
 * They're just defined in the SaleScopes trait now!
 */

/**
 * Using the Enum:
 *
 * $sale->payment_status === PaymentStatus::FULLY_PAID
 * $sale->payment_status->label() // "Fully Paid"
 * $sale->payment_status->color() // "green"
 * $sale->payment_status->hasBalance() // false
 */


