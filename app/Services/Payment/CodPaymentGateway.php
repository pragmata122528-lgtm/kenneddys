<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Log;

class CodPaymentGateway implements PaymentGatewayInterface
{
    /**
     * Process cash on delivery order confirmation.
     */
    public function charge(int $amount, string $currency = 'usd'): bool
    {
        Log::info('Cash on delivery order authorized for fulfillment', [
            'gateway' => 'cod',
            'amount' => $amount,
            'currency' => $currency,
        ]);

        return true;
    }
}
