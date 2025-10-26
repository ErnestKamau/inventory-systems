<?php
// app/Repositories/SaleRepository.php

namespace App\Repositories;

use App\Models\Sale;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

/**
 * Class SaleRepository
 *
 * Handles all database queries for Sales.
 * Abstracts Eloquent implementation from business logic.
 *
 * Benefits:
 * - Single place for all Sale queries
 * - Easy to switch database/ORM if needed
 * - Testable with mock repositories
 * - Keeps controllers thin
 * - Reusable complex queries
 * 
 * @package App\Repositories
 */
class SaleRepository
{
    /**
     * The Sale model instance
     */
    protected $model;

    /**
     * SaleRepository constructor
     *
     * Dependency Injection: Laravel automatically provides the Sale model
     */
    public function __construct(Sale $model)
    {
        $this->model = $model;
    }

    /**
     * Find sale by ID
     *
     * @param int $id
     * @param array $with Relationships to eager load
     * @return Sale|null
     */
    public function find(int $id, array $with = []): ?Sale
    {
        $query = $this->model->newQuery();

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->find($id);
    }

    /**
     * Find sale by ID or fail
     *
     * @param int $id
     * @param array $with
     * @return Sale
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id, array $with = []): Sale
    {
        $query = $this->model->newQuery();

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->findOrFail($id);
    }

    /**
     * Find sale by sale number
     *
     * @param string $saleNumber
     * @return Sale|null
     */
    public function findBySaleNumber(string $saleNumber): ?Sale
    {
        return $this->model->where('sale_number', $saleNumber)->first();
    }

    /**
     * Get all sales with pagination
     *
     * @param int $perPage
     * @param array $with
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $with = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->latest();

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get sales by payment status
     *
     * @param PaymentStatus|string $status
     * @param array $with
     * @return Collection
     */
    public function getByPaymentStatus($status, array $with = []): Collection
    {
        $statusValue = $status instanceof PaymentStatus ? $status->value : $status;

        $query = $this->model->where('payment_status', $statusValue);

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->get();
    }

    /**
     * Get sales with outstanding balance
     *
     * @param array $with
     * @return Collection
     */
    public function getWithBalance(array $with = []): Collection
    {
        $statuses = array_map(fn($s) => $s->value, PaymentStatus::withBalance());

        $query = $this->model->whereIn('payment_status', $statuses);

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->get();
    }

    /**
     * Get overdue sales
     *
     * @param array $with
     * @return Collection
     */
    public function getOverdue(array $with = []): Collection
    {
        $query = $this->model->where('payment_status', PaymentStatus::OVERDUE->value);

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->get();
    }

    /**
     * Get sales nearing due date
     *
     * @param int $days Number of days threshold
     * @param array $with
     * @return Collection
     */
    public function getNearDue(int $days = 2, array $with = []): Collection
    {
        $statuses = [
            PaymentStatus::PARTIAL->value,
            PaymentStatus::NO_PAYMENT->value,
        ];

        $query = $this->model
            ->whereIn('payment_status', $statuses)
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [
                now(),
                now()->addDays($days)
            ]);

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->get();
    }

    /**
     * Get sales for a specific date range
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param array $with
     * @return Collection
     */
    public function getByDateRange(Carbon $startDate, Carbon $endDate, array $with = []): Collection
    {
        $query = $this->model->whereBetween('created_at', [$startDate, $endDate]);

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->get();
    }

    /**
     * Get today's sales
     *
     * @param array $with
     * @return Collection
     */
    public function getToday(array $with = []): Collection
    {
        $query = $this->model->whereDate('created_at', now()->toDateString());

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->get();
    }

    /**
     * Get this week's sales
     *
     * @param array $with
     * @return Collection
     */
    public function getThisWeek(array $with = []): Collection
    {
        $query = $this->model->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->get();
    }

    /**
     * Get this month's sales
     *
     * @param array $with
     * @return Collection
     */
    public function getThisMonth(array $with = []): Collection
    {
        $query = $this->model
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->get();
    }

    /**
     * Get sales by customer phone
     *
     * @param string $phone
     * @param array $with
     * @return Collection
     */
    public function getByCustomerPhone(string $phone, array $with = []): Collection
    {
        $query = $this->model->where('customer_phone', $phone);

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->get();
    }

    /**
     * Create a new sale
     *
     * @param array $data
     * @return Sale
     */
    public function create(array $data): Sale
    {
        return $this->model->create($data);
    }

    /**
     * Update a sale
     *
     * @param Sale $sale
     * @param array $data
     * @return Sale
     */
    public function update(Sale $sale, array $data): Sale
    {
        $sale->update($data);
        return $sale->fresh();
    }

    /**
     * Delete a sale
     *
     * @param Sale $sale
     * @return bool
     */
    public function delete(Sale $sale): bool
    {
        return $sale->delete();
    }

    /**
     * Get sales statistics
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array
     */
    public function getStatistics(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = $this->model->newQuery();

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return [
            'total_sales' => $query->count(),
            'total_revenue' => $query->sum('total_amount'),
            'total_profit' => $query->sum('profit_amount'),
            'total_cost' => $query->sum('cost_amount'),
            'fully_paid_count' => (clone $query)->where('payment_status', PaymentStatus::FULLY_PAID->value)->count(),
            'partial_paid_count' => (clone $query)->where('payment_status', PaymentStatus::PARTIAL->value)->count(),
            'unpaid_count' => (clone $query)->where('payment_status', PaymentStatus::NO_PAYMENT->value)->count(),
            'overdue_count' => (clone $query)->where('payment_status', PaymentStatus::OVERDUE->value)->count(),
            'outstanding_balance' => $this->getOutstandingBalance($startDate, $endDate),
        ];
    }

    /**
     * Get total outstanding balance
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return float
     */
    public function getOutstandingBalance(?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $statuses = array_map(fn($s) => $s->value, PaymentStatus::withBalance());
        
        $query = $this->model->whereIn('payment_status', $statuses);

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $sales = $query->with('payments')->get();

        return $sales->sum(function ($sale) {
            return $sale->balance;
        });
    }

    /**
     * Search sales by customer name or phone
     *
     * @param string $searchTerm
     * @param array $with
     * @return Collection
     */
    public function search(string $searchTerm, array $with = []): Collection
    {
        $query = $this->model->where(function ($q) use ($searchTerm) {
            $q->where('customer_name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('customer_phone', 'LIKE', "%{$searchTerm}%")
              ->orWhere('sale_number', 'LIKE', "%{$searchTerm}%");
        });

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->get();
    }

    /**
     * Get all sales (without pagination)
     *
     * @param array $with
     * @return Collection
     */
    public function all(array $with = []): Collection
    {
        $query = $this->model->newQuery()->latest();

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->get();
    }

    /**
     * Count total sales
     *
     * @param array $conditions Optional where conditions
     * @return int
     */
    public function count(array $conditions = []): int
    {
        $query = $this->model->newQuery();

        foreach ($conditions as $field => $value) {
            $query->where($field, $value);
        }

        return $query->count();
    }

    /**
     * Get sales with specific conditions
     *
     * @param array $conditions
     * @param array $with
     * @param int|null $limit
     * @return Collection
     */
    public function getWhere(array $conditions, array $with = [], ?int $limit = null): Collection
    {
        $query = $this->model->newQuery();

        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        if (!empty($with)) {
            $query->with($with);
        }

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get sales ordered by specific column
     *
     * @param string $column
     * @param string $direction
     * @param array $with
     * @return Collection
     */
    public function getOrderedBy(string $column, string $direction = 'asc', array $with = []): Collection
    {
        $query = $this->model->newQuery()->orderBy($column, $direction);

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->get();
    }

    /**
     * Check if sale exists by sale number
     *
     * @param string $saleNumber
     * @return bool
     */
    public function existsBySaleNumber(string $saleNumber): bool
    {
        return $this->model->where('sale_number', $saleNumber)->exists();
    }

    /**
     * Get latest sale
     *
     * @param array $with
     * @return Sale|null
     */
    public function getLatest(array $with = []): ?Sale
    {
        $query = $this->model->newQuery()->latest();

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->first();
    }

    /**
     * Get sales grouped by payment status
     *
     * @return array
     */
    public function getGroupedByStatus(): array
    {
        return [
            'fully_paid' => $this->getByPaymentStatus(PaymentStatus::FULLY_PAID),
            'partial' => $this->getByPaymentStatus(PaymentStatus::PARTIAL),
            'no_payment' => $this->getByPaymentStatus(PaymentStatus::NO_PAYMENT),
            'overdue' => $this->getByPaymentStatus(PaymentStatus::OVERDUE),
        ];
    }

    /**
     * Get top customers by total purchase amount
     *
     * @param int $limit
     * @return Collection
     */
    public function getTopCustomers(int $limit = 10): Collection
    {
        return $this->model
            ->selectRaw('customer_name, customer_phone, SUM(total_amount) as total_purchased, COUNT(*) as total_orders')
            ->groupBy('customer_name', 'customer_phone')
            ->orderByDesc('total_purchased')
            ->limit($limit)
            ->get();
    }

    /**
     * Get revenue trend (daily, weekly, or monthly)
     *
     * @param string $period 'daily', 'weekly', or 'monthly'
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return Collection
     */
    public function getRevenueTrend(string $period = 'daily', ?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $query = $this->model->newQuery();

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        switch ($period) {
            case 'daily':
                return $query->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue, COUNT(*) as sales_count')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();

            case 'weekly':
                return $query->selectRaw('YEARWEEK(created_at) as week, SUM(total_amount) as revenue, COUNT(*) as sales_count')
                    ->groupBy('week')
                    ->orderBy('week')
                    ->get();

            case 'monthly':
                return $query->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(total_amount) as revenue, COUNT(*) as sales_count')
                    ->groupBy('year', 'month')
                    ->orderBy('year')
                    ->orderBy('month')
                    ->get();

            default:
                return collect();
        }
    }

    /**
     * Bulk update sales
     *
     * @param array $conditions
     * @param array $updates
     * @return int Number of affected rows
     */
    public function bulkUpdate(array $conditions, array $updates): int
    {
        $query = $this->model->newQuery();

        foreach ($conditions as $field => $value) {
            $query->where($field, $value);
        }

        return $query->update($updates);
    }
}

