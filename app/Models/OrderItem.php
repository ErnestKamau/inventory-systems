<?php
// app/Models/OrderItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'kilogram',
        'unit_price',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'kilogram' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'subtotal',
    ];

    /* ========================================
     * RELATIONSHIPS
     * ======================================== */

    /**
     * Get the order this item belongs to
     *
     * Usage: $orderItem->order
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product for this order item
     *
     * Usage: $orderItem->product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /* ========================================
     * ACCESSORS (Computed Properties)
     * ======================================== */

    /**
     * Calculate subtotal for this item
     *
     * Logic:
     * - If sold by weight (kilogram exists): price * weight
     * - If sold by quantity: price * quantity
     *
     * Usage: $orderItem->subtotal
     */
    public function getSubtotalAttribute()
    {
        if ($this->kilogram) {
            return $this->unit_price * $this->kilogram;
        }
        return $this->quantity * $this->unit_price;
    }

    /**
     * Get product name (convenience accessor)
     *
     * Useful for displaying without loading product relationship
     *
     * Usage: $orderItem->product_name
     */
    public function getProductNameAttribute()
    {
        return $this->product ? $this->product->name : 'Unknown Product';
    }

    /* ========================================
     * HELPER METHODS
     * ======================================== */

    /**
     * Check if item is sold by weight
     *
     * Usage: $orderItem->isSoldByWeight()
     */
    public function isSoldByWeight(): bool
    {
        return $this->kilogram !== null && $this->kilogram > 0;
    }

    /**
     * Check if item is sold by quantity
     *
     * Usage: $orderItem->isSoldByQuantity()
     */
    public function isSoldByQuantity(): bool
    {
        return !$this->isSoldByWeight();
    }

    public function __toString()
    {
        return "{$this->product->name} x {$this->quantity}";
    }
}

