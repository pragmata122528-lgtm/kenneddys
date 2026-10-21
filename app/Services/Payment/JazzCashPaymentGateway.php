<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Log;

class JazzCashPaymentGateway implements PaymentGatewayInterface
{
    public function charge(int $amount, string $currency = 'PKR'): bool
    {
        $shouldSucceed = config('services.jazzcash.mock_success', true);

        Log::info('JazzCash payment transaction processed', [
            'gateway' => 'jazzcash',
            'amount' => $amount,
            'currency' => $currency,
            'status' => $shouldSucceed ? 'succeeded' : 'failed',
        ]);

        return (bool) $shouldSucceed;
    }
}
