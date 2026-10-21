<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentFailedException extends Exception
{
    public function __construct(string $message = 'Payment processing failed. Your order has been rolled back.')
    {
        parent::__construct($message, 402);
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => 'PAYMENT_FAILED',
        ], 402);
    }
}
