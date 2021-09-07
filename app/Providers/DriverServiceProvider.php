<?php

namespace App\Providers;

use App\Drivers\AbsoluteDriver;
use App\Drivers\AlfaMskDriver;
use App\Drivers\RensinsDriver;
use App\Drivers\SberinsDriver;
use App\Drivers\Services\MerchantServices;
use App\Drivers\Source\Alpha\AlfaAuth;
use App\Drivers\Source\Renins\ReninsClientService;
use App\Printers\PolicyPrinter;
use App\Services\DriverService;
use App\Services\HttpClientService;
use App\Services\PaymentService;
use Artisaninweb\SoapWrapper\SoapWrapper;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Strahovka\Payment\PayService;

/**
 * Class DriverServiceProvider
 *
 * @package App\Providers
 */
class DriverServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(
            DriverService::class,
            function ($app)
            {
                return new DriverService($this->app->make(PolicyPrinter::class));
            }
        );
        $this->app->singleton(
            AbsoluteDriver::code(),
            function ($app)
            {
                $repository = config();
                $paymentService = App::make(
                    PaymentService::class,
                    ['host' => $repository->get('mortgage.absolut_77.pay_host')]
                );

                return new AbsoluteDriver(
                    new Client(),
                    $paymentService,
                    $repository->get('mortgage.absolut_77.base_Url'),
                    $repository->get('mortgage.absolut_77.client_id'),
                    $repository->get('mortgage.absolut_77.client_secret'),
                    $repository->get('mortgage.absolut_77.pdf.path'),
                    $repository->get('mortgage.absolut_77.grant_type'),
                    $repository->get('mortgage.absolut_77.actions')
                );
            }
        );

        $this->app->singleton(
            AlfaMskDriver::code(),
            function ($app)
            {
                $repository = config();

                return new AlfaMskDriver(
                    new Client(),
                    new AlfaAuth(
                        $repository->get('mortgage.alfa_msk.auth.username'),
                        $repository->get('mortgage.alfa_msk.auth.pass'),
                        $repository->get('mortgage.alfa_msk.auth.auth_url')
                    ),
                    new MerchantServices($repository->get('mortgage.alfa_msk.merchan_host')),
                    $repository->get('mortgage.alfa_msk.host'),
                    $repository->get('mortgage.alfa_msk.actions'),
                    $repository->get('mortgage.alfa_msk.numberIterations', 5)
                );
            }
        );

        $this->app->singleton(
            RensinsDriver::code(),
            function ($app)
            {
                return new RensinsDriver($this->app->make(ReninsClientService::class));
            }
        );

        $this->app->singleton(
            SberinsDriver::code(),
            function ($app)
            {
                return new SberinsDriver();
            }
        );

        $this->app->singleton(
            ReninsClientService::class,
            function ($app)
            {
                $repository = config();
                $clientService = new HttpClientService(
                    $repository->get('mortgage.rensins.host'),
                    [
                        'curl' => [CURLOPT_SSL_VERIFYPEER => false],
                        'verify' => false,
                    ],
                    $repository->get('mortgage.rensins.login'),
                    $repository->get('mortgage.rensins.pass')
                );

                return new ReninsClientService(
                    $clientService,
                    $repository->get('mortgage.rensins.actions'),
                    $repository->get('mortgage.rensins.temp_path')
                );
            }
        );

        $this->app->bind(
            PayService::class,
            function ($app)
            {
                return new PayService(new SoapWrapper());
            }
        );

        $this->app->singleton(PolicyPrinter::class, function ($app) {
            return new PolicyPrinter(config('ns.pdf'));
        });
    }
}
