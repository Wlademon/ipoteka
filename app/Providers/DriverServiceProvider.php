<?php

namespace App\Providers;

use App\Services\DriverService;
use Artisaninweb\SoapWrapper\SoapWrapper;
use Illuminate\Support\ServiceProvider;
use Strahovka\Payment\PayService;

/**
 * Class DriverServiceProvider
 * @package App\Providers
 */
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
        $drivers = config('mortgage.drivers');
        foreach ($drivers as $code => $driver) {
            $this->app->singleton($code, function($app) use ($driver, $code) {
                return new $driver(config(), "mortgage.{$code}.");
            });
        }

        $this->app->bind(PayService::class, function ($app) {
            if (in_array(config('app.env'), ['local', 'testing'])) {
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
