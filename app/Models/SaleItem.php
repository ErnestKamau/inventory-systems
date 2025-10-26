<?php
// app/Models/SaleItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $table = 'sale_items';

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'cost_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'subtotal',
        'profit_total',
    ];

    /* ========================================
     * RELATIONSHIPS
     * ======================================== */

    /**
     * Get the sale this item belongs to
     *
     * Usage: $saleItem->sale
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the product for this sale item
     *
     * Usage: $saleItem->product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /* ========================================
     * ACCESSORS (Computed Properties)
     * ======================================== */

    /**
     * Calculate subtotal (revenue for this item)
     *
     * Usage: $saleItem->subtotal
     */
    public function getSubtotalAttribute()
    {
        return $this->quantity * $this->unit_price;
    }

    /**
     * Calculate total profit for this item
     *
     * Formula: (selling_price - cost_price) * quantity
     *
     * Usage: $saleItem->profit_total
     */
    public function getProfitTotalAttribute()
    {
        return ($this->unit_price - $this->cost_price) * $this->quantity;
    }

    /**
     * Calculate profit margin percentage
     *
     * Usage: $saleItem->profit_margin
     */
    public function getProfitMarginAttribute()
    {
        if ($this->cost_price > 0) {
            return (($this->unit_price - $this->cost_price) / $this->cost_price) * 100;
        }
        return 0;
    }

    /**
     * Get total cost for this item
     *
     * Usage: $saleItem->cost_total
     */
    public function getCostTotalAttribute()
    {
        return $this->quantity * $this->cost_price;
    }

    /* ========================================
     * HELPER METHODS
     * ======================================== */

    public function __toString()
    {
        return "{$this->product->name} x {$this->quantity}";
    }
}


