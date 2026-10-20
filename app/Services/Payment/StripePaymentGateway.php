<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Log;

class StripePaymentGateway implements PaymentGatewayInterface
{
    /**
     * Charge the given amount in the specified currency.
     */
    public function charge(int $amount, string $currency = 'usd'): bool
    {
        $shouldSucceed = config('services.stripe.mock_success', true);

        Log::info('Stripe payment transaction processed', [
            'gateway' => 'stripe',
            'amount' => $amount,
            'currency' => $currency,
            'status' => $shouldSucceed ? 'succeeded' : 'failed',
        ]);

        return (bool) $shouldSucceed;
    }
}
