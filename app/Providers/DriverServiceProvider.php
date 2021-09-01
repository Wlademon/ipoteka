<?php

namespace App\Providers;

use App\Drivers\AbsoluteDriver;
use App\Drivers\AlfaMskDriver;
use App\Drivers\Services\MerchantServices;
use App\Drivers\Source\Alpha\AlfaAuth;
use App\Drivers\Source\Renins\ReninsClientService;
use App\Services\DriverService;
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
    public function register()
    {
        $this->app->singleton(
            DriverService::class,
            function ($app)
            {
                return new DriverService();
            }
        );
        $this->app->singleton(
            AbsoluteDriver::class,
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
                    $repository->get('mortgage.absolut_77.calculate_life_path'),
                    $repository->get('mortgage.absolut_77.calculate_property_path'),
                    $repository->get('mortgage.absolut_77.life_agreement_path'),
                    $repository->get('mortgage.absolut_77.property_agreement_path'),
                    $repository->get('mortgage.absolut_77.print_policy_path'),
                    $repository->get('mortgage.absolut_77.released_policy_path')
                );
            }
        );

        $this->app->singleton(
            AlfaMskDriver::class,
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
                    $repository->get('mortgage.alfa_msk.numberIterations', 5)
                );
            }
        );

        $this->app->singleton(
            ReninsClientService::class,
            function ($app)
            {
                $repository = config();

                return new ReninsClientService(
                    $repository->get('mortgage.rensins.host'),
                    $repository->get('mortgage.rensins.login'),
                    $repository->get('mortgage.rensins.pass')
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
    }
}
