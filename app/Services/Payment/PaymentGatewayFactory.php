<?php

namespace App\Services\Payment;

use InvalidArgumentException;

class PaymentGatewayFactory
{
    /**
     * Resolve the requested payment gateway strategy.
     */
    public function make(string $method = 'stripe'): PaymentGatewayInterface
    {
        return match (strtolower($method)) {
            'stripe' => app(PaymentGatewayInterface::class),
            'cod' => app(CodPaymentGateway::class),
            default => throw new InvalidArgumentException("Unsupported payment gateway driver: [{$method}]."),
        };
    }
}
