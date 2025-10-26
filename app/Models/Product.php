<?php
// app/Models/Product.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'name',
        'image_url',
        'category_id',
        'description',
        'kilograms',
        'sale_price',
        'cost_price',
        'in_stock',
        'minimum_stock',
        'is_active',
    ];

    protected $casts = [
        'kilograms' => 'decimal:3',
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'in_stock' => 'integer',
        'minimum_stock' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Append computed attributes to JSON/Array output
     *
     * When you do: Product::first()->toArray()
     * These attributes will be included automatically
     */
    protected $appends = [
        'profit_margin',
        'is_low_stock',
    ];

    /* ========================================
     * RELATIONSHIPS
     * ======================================== */

    /**
     * Get the category this product belongs to
     *
     * Usage: $product->category
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get all order items for this product
     *
     * Usage: $product->orderItems
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get all sale items for this product
     *
     * Usage: $product->saleItems
     */
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    /* ========================================
     * QUERY SCOPES (Custom Query Builders)
     * ======================================== */

    /**
     * Filter only active products
     *
     * Usage: Product::active()->get()
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Filter products with low stock
     *
     * Explanation:
     * - DB::raw('minimum_stock') allows comparing two columns
     * - Excludes products with no minimum set (minimum_stock = 0)
     *
     * Usage: Product::lowStock()->get()
     */
    public function scopeLowStock(Builder $query)
    {
        return $query->active()
                     ->whereColumn('in_stock', '<=', 'minimum_stock')
                     ->where('minimum_stock', '>', 0);
    }

    /**
     * Filter by specific category
     *
     * Usage: Product::byCategory(5)->get()
     */
    public function scopeByCategory(Builder $query, $categoryId)
    {
        return $query->active()->where('category_id', $categoryId);
    }

    /**
     * Search products by name or description
     *
     * Usage: Product::search('laptop')->get()
     */
    public function scopeSearch(Builder $query, $searchTerm)
    {
        return $query->active()->where(function ($q) use ($searchTerm) {
            $q->where('name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('description', 'LIKE', "%{$searchTerm}%");
        });
    }

    /**
     * Filter by price range
     *
     * Usage: Product::byPriceRange(100, 500)->get()
     */
    public function scopeByPriceRange(Builder $query, $minPrice, $maxPrice)
    {
        return $query->active()
                     ->whereBetween('sale_price', [$minPrice, $maxPrice]);
    }

    /**
     * Products that need reordering (same as lowStock)
     *
     * Usage: Product::needsReorder()->get()
     */
    public function scopeNeedsReorder(Builder $query)
    {
        return $this->scopeLowStock($query);
    }

    /* ========================================
     * ACCESSORS (Computed Properties)
     * ======================================== */

    /**
     * Calculate profit margin percentage
     *
     * Formula: ((sale_price - cost_price) / cost_price) * 100
     *
     * Usage: $product->profit_margin
     * Returns: 25.50 (means 25.5% profit)
     */
    public function getProfitMarginAttribute()
    {
        if ($this->cost_price > 0 && $this->sale_price > 0) {
            return (($this->sale_price - $this->cost_price) / $this->cost_price) * 100;
        }
        return 0;
    }

    /**
     * Check if product stock is low
     *
     * Usage: $product->is_low_stock
     * Returns: true/false
     */
    public function getIsLowStockAttribute()
    {
        return $this->in_stock <= $this->minimum_stock && $this->minimum_stock > 0;
    }

    /**
     * Calculate potential profit per unit
     *
     * Usage: $product->unit_profit
     */
    public function getUnitProfitAttribute()
    {
        return $this->sale_price - $this->cost_price;
    }

    /**
     * Calculate total inventory value (cost basis)
     *
     * Usage: $product->inventory_value
     */
    public function getInventoryValueAttribute()
    {
        return $this->in_stock * $this->cost_price;
    }

    /**
     * Calculate potential revenue if all stock sold
     *
     * Usage: $product->potential_revenue
     */
    public function getPotentialRevenueAttribute()
    {
        return $this->in_stock * $this->sale_price;
    }

    /* ========================================
     * HELPER METHODS
     * ======================================== */

    /**
     * Decrease stock by quantity
     *
     * Usage: $product->decrementStock(5)
     *
     * @param int $quantity
     * @throws \Exception if insufficient stock
     */
    public function decrementStock(int $quantity)
    {
        if ($this->in_stock < $quantity) {
            throw new \Exception("Insufficient stock for product: {$this->name}");
        }

        $this->decrement('in_stock', $quantity);
    }

    /**
     * Increase stock by quantity
     *
     * Usage: $product->incrementStock(10)
     *
     * @param int $quantity
     */
    public function incrementStock(int $quantity)
    {
        $this->increment('in_stock', $quantity);
    }

    /**
     * Check if product can fulfill an order
     *
     * Usage: $product->canFulfillOrder(20)
     *
     * @param int $quantity
     * @return bool
     */
    public function canFulfillOrder(int $quantity): bool
    {
        return $this->in_stock >= $quantity;
    }

    public function __toString()
    {
        return $this->name;
    }
}
