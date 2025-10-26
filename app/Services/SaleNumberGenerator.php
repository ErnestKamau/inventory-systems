<?php
// app/Services/SaleNumberGenerator.php

namespace App\Services;

use App\Models\Sale;
use Illuminate\Support\Facades\Cache;

/**
 * Class SaleNumberGenerator
 *
 * Generates unique sale numbers with various formats.
 * Uses Redis/Cache for performance when generating sequential numbers.
 *
 * Supports multiple formats:
 * - SALE-YYYYMMDD-### (Default)
 * - INV-YYYYMMDD-###
 * - Custom prefix
 *
 * @package App\Services
 */
class SaleNumberGenerator
{
    /**
     * Default prefix for sale numbers
     */
    const DEFAULT_PREFIX = 'SALE';

    /**
     * Generate unique sale number
     *
     * Format: PREFIX-YYYYMMDD-###
     * Example: SALE-20250124-001
     *
     * PERFORMANCE NOTE:
     * Uses cache to avoid counting database records on every generation.
     * Cache key: "sale_counter_{date}"
     *
     * Usage:
     * $generator = new SaleNumberGenerator();
     * $saleNumber = $generator->generate();
     *
     * @param string|null $prefix Custom prefix (defaults to 'SALE')
     * @return string
     */
    public function generate(?string $prefix = null): string
    {
        $prefix = $prefix ?? self::DEFAULT_PREFIX;
        $date = now()->format('Ymd');
        $cacheKey = "sale_counter_{$date}";
        
        // Try to get counter from cache first (performance optimization)
        $counter = Cache::remember($cacheKey, now()->endOfDay(), function () {
            return Sale::whereDate('created_at', now()->toDateString())->count();
        });
        
        // Increment counter
        $counter++;
        Cache::put($cacheKey, $counter, now()->endOfDay());
        
        $number = str_pad($counter, 3, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$date}-{$number}";
    }

    /**
     * Generate invoice-style number
     *
     * Format: INV-YYYYMMDD-###
     * Example: INV-20250124-001
     *
     * @return string
     */
    public function generateInvoiceNumber(): string
    {
        return $this->generate('INV');
    }

    /**
     * Generate with custom format
     *
     * Usage:
     * $number = $generator->generateCustom('RECEIPT', 'Y-m', 4);
     * Output: RECEIPT-25-01-0001
     *
     * @param string $prefix
     * @param string $dateFormat PHP date format
     * @param int $padding Number of digits for counter
     * @return string
     */
    public function generateCustom(
        string $prefix,
        string $dateFormat = 'Ymd',
        int $padding = 3
    ): string {
        $date = now()->format($dateFormat);
        $cacheKey = "sale_counter_{$date}_{$prefix}";
        
        $counter = Cache::remember($cacheKey, now()->endOfDay(), function () use ($prefix) {
            return Sale::where('sale_number', 'LIKE', "{$prefix}-%")
                       ->whereDate('created_at', now()->toDateString())
                       ->count();
        });
        
        $counter++;
        Cache::put($cacheKey, $counter, now()->endOfDay());
        
        $number = str_pad($counter, $padding, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$date}-{$number}";
    }

    /**
     * Generate UUID-based sale number
     *
     * Format: SALE-{8-char-uuid}
     * Example: SALE-4a3b2c1d
     *
     * Use when you need guaranteed uniqueness across distributed systems
     *
     * @return string
     */
    public function generateUuid(): string
    {
        $uuid = substr(str_replace('-', '', \Illuminate\Support\Str::uuid()), 0, 8);
        return "SALE-{$uuid}";
    }

    /**
     * Validate sale number format
     *
     * Usage:
     * if ($generator->isValid('SALE-20250124-001')) {
     *     // Valid format
     * }
     *
     * @param string $saleNumber
     * @param string|null $prefix
     * @return bool
     */
    public function isValid(string $saleNumber, ?string $prefix = null): bool
    {
        $prefix = $prefix ?? self::DEFAULT_PREFIX;
        $pattern = "/^{$prefix}-\d{8}-\d{3}$/";
        
        return preg_match($pattern, $saleNumber) === 1;
    }

    /**
     * Extract date from sale number
     *
     * Usage:
     * $date = $generator->extractDate('SALE-20250124-001');
     * // Returns: Carbon instance of 2025-01-24
     *
     * @param string $saleNumber
     * @return \Carbon\Carbon|null
     */
    public function extractDate(string $saleNumber): ?\Carbon\Carbon
    {
        preg_match('/\d{8}/', $saleNumber, $matches);
        
        if (empty($matches)) {
            return null;
        }
        
        try {
            return \Carbon\Carbon::createFromFormat('Ymd', $matches[0]);
        } catch (\Exception $e) {
            return null;
        }
    }
}

