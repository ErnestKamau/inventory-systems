<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{

    protected $table = "categories";

    protected $fillable = [
        "name",
        "is_active",
    ];

    protected $casts = [
        "is_active" => "boolean",
        "created_at" => "datetime",
        "updated_at" => "datetime",
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where("is_Active", true);
    }


    /**
     * Get categories with at least one active product
     *
     * Usage: Category::withActiveProducts()->get()
     */
    public function scopeActiveProducts($query)
    {
        return $query->whereHas('products', function($q) {
            $q->where('is_active', true);
        });
    }


    /* ========================================
     * ACCESSORS
     * ======================================== */

    /**
     * Get count of active products in this category
     *
     * Usage: $category->active_products_count
     */
    public function getActiveProductsCountAttribute()
    {
        return $this->products()->where('is_active', true)->count();
    }

    /**
     * Check if category has any products
     *
     * Usage: $category->has_products
     */
    public function getHasProductsAttribute(){
        return $this->products()->exists();
    }

    public function __toString()
    {
        return $this->name;
    }
}
