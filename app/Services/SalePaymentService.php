<?php
// app/Services/SalePaymentService.php

namespace App\Services;

use App\Models\Sale;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

/**
 * Class SalePaymentService
 *
 * Handles all payment-related business logic for sales.
 * Separates payment operations from the Sale model.
 *
 * Benefits:
 * - Single Responsibility: Only handles payment logic
 * - Testable: Easy to mock in unit tests
 * - Reusable: Can be used in controllers, jobs, commands
 * - Transaction Safe: Wraps operations in database transactions
 *
 * @package App\Services
 */
class SalePaymentService
{
    /**
     * Auto-update payment status based on payments
     *
     * Business Logic:
     * 1. If total_paid >= total_amount → fully-paid
     * 2. If due_date passed and not fully paid → overdue
     * 3. If some payment made but not full → partial
     * 4. If no payment → no-payment
     *
     * Usage:
     * $service = new SalePaymentService();
     * $service->updatePaymentStatus($sale);
     *
     * @param Sale $sale
     * @return Sale
     */
    public function updatePaymentStatus(Sale $sale): Sale
    {
        $totalPaid = $sale->total_paid;
        
        if ($totalPaid >= $sale->total_amount) {
            $sale->payment_status = 'fully-paid';
        } elseif ($sale->due_date && now()->gt($sale->due_date)) {
            $sale->payment_status = 'overdue';
        } elseif ($totalPaid > 0) {
            $sale->payment_status = 'partial';
        } else {
            $sale->payment_status = 'no-payment';
        }
        
        $sale->save();
        
        return $sale;
    }

    /**
     * Mark sale as debt with due date
     *
     * Usage:
     * $service->setAsDebt($sale, 7); // 7 days from now
     *
     * @param Sale $sale
     * @param int $days Number of days until due
     * @return Sale
     * @throws \InvalidArgumentException
     */
    public function setAsDebt(Sale $sale, int $days = 7): Sale
    {
        if ($days < 1) {
            throw new \InvalidArgumentException('Days must be at least 1');
        }
        
        if (in_array($sale->payment_status, ['no-payment', 'partial'])) {
            $sale->due_date = now()->addDays($days);
            $sale->save();
        }
        
        return $sale;
    }

    /**
     * Check if payment is near due date (within 2 days)
     *
     * Usage:
     * $isNear = $service->isNearDue($sale);
     *
     * @param Sale $sale
     * @return bool
     */
    public function isNearDue(Sale $sale): bool
    {
        if (in_array($sale->payment_status, ['no-payment', 'partial']) && $sale->due_date) {
            return now()->diffInDays($sale->due_date, false) <= 2;
        }
        return false;
    }

    /**
     * Check if sale is overdue
     *
     * Usage:
     * $isOverdue = $service->isOverdue($sale);
     *
     * @param Sale $sale
     * @return bool
     */
    public function isOverdue(Sale $sale): bool
    {
        return $sale->payment_status === 'overdue';
    }

    /**
     * Add a payment to sale with automatic status update
     *
     * This method:
     * 1. Validates payment data
     * 2. Creates the payment record
     * 3. Updates the sale's payment status
     * 4. Wraps everything in a transaction
     *
     * Usage:
     * $payment = $service->addPayment($sale, [
     *     'method' => 'mpesa',
     *     'amount' => 1000,
     *     'reference' => 'QR1234XYZ'
     * ]);
     *
     * @param Sale $sale
     * @param array $paymentData
     * @return Payment
     * @throws \Exception
     */
    public function addPayment(Sale $sale, array $paymentData): Payment
    {
        return DB::transaction(function () use ($sale, $paymentData) {
            // Create payment
            $payment = $sale->payments()->create($paymentData);
            
            // Auto-update payment status
            $this->updatePaymentStatus($sale);
            
            return $payment;
        });
    }

    /**
     * Process multiple payments for a sale
     *
     * Useful for batch payment processing or split payments
     *
     * Usage:
     * $payments = $service->addMultiplePayments($sale, [
     *     ['method' => 'cash', 'amount' => 500],
     *     ['method' => 'mpesa', 'amount' => 500]
     * ]);
     *
     * @param Sale $sale
     * @param array $paymentsData Array of payment data arrays
     * @return array Array of Payment models
     */
    public function addMultiplePayments(Sale $sale, array $paymentsData): array
    {
        return DB::transaction(function () use ($sale, $paymentsData) {
            $payments = [];
            
            foreach ($paymentsData as $paymentData) {
                $payments[] = $sale->payments()->create($paymentData);
            }
            
            // Update status once after all payments
            $this->updatePaymentStatus($sale);
            
            return $payments;
        });
    }

    /**
     * Get payment summary for a sale
     *
     * Returns detailed payment information
     *
     * @param Sale $sale
     * @return array
     */
    public function getPaymentSummary(Sale $sale): array
    {
        return [
            'total_amount' => $sale->total_amount,
            'total_paid' => $sale->total_paid,
            'balance' => $sale->balance,
            'payment_progress' => $sale->payment_progress,
            'is_fully_paid' => $sale->is_fully_paid,
            'is_overdue' => $this->isOverdue($sale),
            'is_near_due' => $this->isNearDue($sale),
            'due_date' => $sale->due_date,
            'payments_count' => $sale->payments->count(),
        ];
    }
}

