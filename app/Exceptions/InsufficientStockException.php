<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InsufficientStockException extends Exception
{
    public function __construct(
        string $message = 'One or more items in your cart do not have sufficient stock.',
        protected array $details = []
    ) {
        parent::__construct($message, 422);
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => 'INSUFFICIENT_STOCK',
            'details' => $this->details,
        ], 422);
    }
}
