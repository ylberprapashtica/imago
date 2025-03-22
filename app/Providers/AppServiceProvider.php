<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->singleton(Client::class, function ($app) {
            return ClientBuilder::create()
                ->setHosts([env('ELASTICSEARCH_HOST', 'localhost') . ':' . env('ELASTICSEARCH_PORT', '9200')])
                ->setBasicAuthentication(
                    env('ELASTICSEARCH_USERNAME', ''),
                    env('ELASTICSEARCH_PASSWORD', '')
                )
                ->setSSLVerification(env('ELASTICSEARCH_SSL_VERIFY', false))
                ->setRetries(env('ELASTICSEARCH_RETRIES', 3))
                ->build();
        });
    }
}
