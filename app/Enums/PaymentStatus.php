<?php
// app/Enums/PaymentStatus.php

namespace App\Enums;

/**
 * Enum PaymentStatus
 *
 * Represents all possible payment statuses for a sale.
 *
 * Benefits of using Enums:
 * 1. Type Safety - No typos like 'ful-paid' instead of 'fully-paid'
 * 2. IDE Autocomplete - Your editor suggests valid values
 * 3. Refactoring Safe - Change value in one place
 * 4. Self-Documenting - All valid statuses in one place
 * 5. Can add methods - Business logic attached to status
 *
 * PHP 8.1+ feature
 *
 * @package App\Enums
 */
enum PaymentStatus: string
{
    case FULLY_PAID = 'fully-paid';
    case PARTIAL = 'partial';
    case NO_PAYMENT = 'no-payment';
    case OVERDUE = 'overdue';

    /**
     * Get human-readable label
     *
     * Usage:
     * PaymentStatus::FULLY_PAID->label(); // "Fully Paid"
     *
     * @return string
     */
    public function label(): string
    {
        return match($this) {
            self::FULLY_PAID => 'Fully Paid',
            self::PARTIAL => 'Partially Paid',
            self::NO_PAYMENT => 'No Payment',
            self::OVERDUE => 'Overdue',
        };
    }

    /**
     * Get badge color for UI
     *
     * Usage:
     * PaymentStatus::FULLY_PAID->color(); // "green"
     *
     * @return string
     */
    public function color(): string
    {
        return match($this) {
            self::FULLY_PAID => 'green',
            self::PARTIAL => 'yellow',
            self::NO_PAYMENT => 'gray',
            self::OVERDUE => 'red',
        };
    }

    /**
     * Check if status indicates outstanding balance
     *
     * Usage:
     * if (PaymentStatus::PARTIAL->hasBalance()) {
     *     // Send reminder
     * }
     *
     * @return bool
     */
    public function hasBalance(): bool
    {
        return in_array($this, [
            self::PARTIAL,
            self::NO_PAYMENT,
            self::OVERDUE,
        ]);
    }

    /**
     * Check if status is fully paid
     *
     * @return bool
     */
    public function isFullyPaid(): bool
    {
        return $this === self::FULLY_PAID;
    }

    /**
     * Check if status requires action
     *
     * @return bool
     */
    public function requiresAction(): bool
    {
        return in_array($this, [
            self::NO_PAYMENT,
            self::OVERDUE,
        ]);
    }

    /**
     * Get all statuses with outstanding balance
     *
     * Usage:
     * $statuses = PaymentStatus::withBalance();
     * // Returns: [PARTIAL, NO_PAYMENT, OVERDUE]
     *
     * @return array
     */
    public static function withBalance(): array
    {
        return [
            self::PARTIAL,
            self::NO_PAYMENT,
            self::OVERDUE,
        ];
    }

    /**
     * Get icon for status (for UI)
     *
     * @return string
     */
    public function icon(): string
    {
        return match($this) {
            self::FULLY_PAID => '✓',
            self::PARTIAL => '◐',
            self::NO_PAYMENT => '○',
            self::OVERDUE => '⚠',
        };
    }

    /**
     * Convert to array for API responses
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label(),
            'color' => $this->color(),
            'icon' => $this->icon(),
            'has_balance' => $this->hasBalance(),
            'requires_action' => $this->requiresAction(),
        ];
    }
}



