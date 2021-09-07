<?php

namespace App\Providers;

use App\Printers\Base64Trait;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * Class AppServiceProvider
 *
 * @package App\Providers
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $conf = config('mortgage');
        $this->app->singleton(PaymentService::class, fn($app) => new PaymentService($conf['mercuriusHost']));
        Blade::directive('image_to_base64', function (string $imageFile) {
            $class =  new class {
                use Base64Trait;

                public function execute(string $file) : string
                {
                    $file = resource_path($file);
                    return $this->encodeFileBase64($file);
                }
            };
            return $class->execute($imageFile);
        });

    }
}
