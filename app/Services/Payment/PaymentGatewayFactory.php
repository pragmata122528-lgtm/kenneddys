<?php

namespace App\Services\Payment;

use InvalidArgumentException;

class PaymentGatewayFactory
{
    public function make(string $method = 'jazzcash'): PaymentGatewayInterface
    {
        return match (strtolower($method)) {
            'jazzcash' => app(PaymentGatewayInterface::class),
            'easypaisa' => app(EasypaisaPaymentGateway::class),
            'cod' => app(CodPaymentGateway::class),
            default => throw new InvalidArgumentException("Unsupported payment gateway driver: [{$method}]."),
        };
    }
}
