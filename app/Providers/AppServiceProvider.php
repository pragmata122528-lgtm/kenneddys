<?php

namespace App\Providers;

use App\Events\OrderPlaced;
use App\Listeners\ProcessOrderFulfillment;
use App\Services\Payment\JazzCashPaymentGateway;
use App\Services\Payment\PaymentGatewayInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, JazzCashPaymentGateway::class);
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Event::listen(OrderPlaced::class, ProcessOrderFulfillment::class);
    }
}
