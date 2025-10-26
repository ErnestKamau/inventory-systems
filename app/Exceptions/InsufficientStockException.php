<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    protected $code = 422; // Unprocessable Entity
    
    public function __construct(string $productName, int $requested, int $available)
    {
        $message = "Insufficient stock for product '{$productName}'. Requested: {$requested}, Available: {$available}";
        parent::__construct($message, $this->code);
    }
}

