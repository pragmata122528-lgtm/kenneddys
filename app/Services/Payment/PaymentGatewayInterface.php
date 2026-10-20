<?php

namespace App\Services\Payment;

interface PaymentGatewayInterface
{
    /**
     * Charge the given amount in the specified currency.
     */
    public function charge(int $amount, string $currency): bool;
}
