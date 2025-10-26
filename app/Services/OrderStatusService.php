<?php

// app/Services/OrderStatusService.php
namespace App\Services;

use App\Models\Order;

class OrderStatusService
{
    public function markAsConfirmed(Order $order): void
    {
        $order->update(['status' => 'confirmed']);
    }

    public function markAsCancelled(Order $order): void
    {
        $order->update(['status' => 'cancelled']);
    }

    public function markAsPaid(Order $order): void
    {
        $order->update(['payment_status' => 'PAID']);
    }

    public function canConvertToSale(Order $order): bool
    {
        return $order->status === 'confirmed' && !$order->has_sale;
    }

    public function hasAvailableStock(Order $order): bool
    {
        foreach ($order->items as $item) {
            if (!$item->product->canFulfillOrder($item->quantity)) {
                return false;
            }
        }
        return true;
    }
}
