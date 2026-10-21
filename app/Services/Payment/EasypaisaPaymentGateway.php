<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Log;

class EasypaisaPaymentGateway implements PaymentGatewayInterface
{
    public function charge(int $amount, string $currency = 'PKR'): bool
    {
        $shouldSucceed = config('services.easypaisa.mock_success', true);

        Log::info('Easypaisa payment transaction processed', [
            'gateway' => 'easypaisa',
            'amount' => $amount,
            'currency' => $currency,
            'status' => $shouldSucceed ? 'succeeded' : 'failed',
        ]);

        return (bool) $shouldSucceed;
    }
}
