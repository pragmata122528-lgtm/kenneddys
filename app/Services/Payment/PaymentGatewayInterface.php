<?php

namespace App\Services\Payment;

interface PaymentGatewayInterface
{
    public function charge(int $amount, string $currency = 'PKR'): bool;
}
