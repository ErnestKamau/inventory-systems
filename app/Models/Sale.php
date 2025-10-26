<?php
// app/Models/Sale.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Sale extends Model
{
    use HasFactory;

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
     * QUERY SCOPES
     * ======================================== */

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

    /* ========================================
     * HELPER METHODS
     * ======================================== */

    /**
     * Auto-update payment status based on payments
     *
     * Business Logic:
     * 1. If total_paid >= total_amount → fully-paid
     * 2. If due_date passed and not fully paid → overdue
     * 3. If some payment made but not full → partial
     * 4. If no payment → no-payment
     *
     * Usage: $sale->updatePaymentStatus()
     */
    public function updatePaymentStatus()
    {
        $totalPaid = $this->total_paid;
        
        if ($totalPaid >= $this->total_amount) {
            $this->payment_status = 'fully-paid';
        } elseif ($this->due_date && now()->gt($this->due_date)) {
            $this->payment_status = 'overdue';
        } elseif ($totalPaid > 0) {
            $this->payment_status = 'partial';
        } else {
            $this->payment_status = 'no-payment';
        }
        
        $this->save();
    }

    /**
     * Mark sale as debt with due date
     *
     * Usage: $sale->setAsDebt(7) // 7 days from now
     *
     * @param int $days Number of days until due
     */
    public function setAsDebt(int $days = 7)
    {
        if (in_array($this->payment_status, ['no-payment', 'partial'])) {
            $this->due_date = now()->addDays($days);
            $this->save();
        }
    }

    /**
     * Check if payment is near due date (within 2 days)
     *
     * Usage: $sale->isNearDue()
     *
     * @return bool
     */
    public function isNearDue(): bool
    {
        if (in_array($this->payment_status, ['no-payment', 'partial']) && $this->due_date) {
            return now()->diffInDays($this->due_date, false) <= 2;
        }
        return false;
    }

    /**
     * Check if sale is overdue
     *
     * Usage: $sale->isOverdue()
     *
     * @return bool
     */
    public function isOverdue(): bool
    {
        return $this->payment_status === 'overdue';
    }

    /**
     * Add a payment to this sale
     *
     * Usage: $sale->addPayment([
     *     'method' => 'mpesa',
     *     'amount' => 1000,
     *     'reference' => 'QR1234XYZ'
     * ])
     *
     * @param array $paymentData
     * @return Payment
     */
    public function addPayment(array $paymentData): Payment
    {
        $payment = $this->payments()->create($paymentData);
        $this->updatePaymentStatus();
        
        return $payment;
    }

    /**
     * Generate unique sale number
     *
     * Format: SALE-YYYYMMDD-###
     * Example: SALE-20250124-001
     *
     * Usage: Sale::generateSaleNumber()
     *
     * @return string
     */
    public static function generateSaleNumber(): string
    {
        $date = now()->format('Ymd');
        $todayCount = static::whereDate('created_at', now()->toDateString())->count();
        $number = str_pad($todayCount + 1, 3, '0', STR_PAD_LEFT);
        
        return "SALE-{$date}-{$number}";
    }

    public function __toString()
    {
        return "{$this->sale_number} - {$this->customer_name} - {$this->total_amount}";
    }
}



