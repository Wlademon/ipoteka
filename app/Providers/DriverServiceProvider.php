<?php

namespace App\Providers;

use App\Services\DriverService;
use Artisaninweb\SoapWrapper\SoapWrapper;
use Illuminate\Support\ServiceProvider;
use Strahovka\Payment\PayService;

class DriverServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(DriverService::class, function ($app) {
            return new DriverService();
        });

        $this->app->bind(PayService::class, function ($app) {
            if (in_array(env('APP_ENV'), ['local', 'testing'])) {
                return new class (new SoapWrapper()) extends PayService {
                    public function __construct(SoapWrapper $soapWrapper) {
                        parent::__construct($soapWrapper);
                    }

                    public function getOrderStatus($orderId) {
                        return [
                            'status' => 'Оплачено, test',
                            'isPayed' => true
                        ];
                    }
                };
            }

            return new PayService(new SoapWrapper());
        });
    }
}