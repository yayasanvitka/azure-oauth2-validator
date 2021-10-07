<?php

namespace Yayasanvitka\AzureOauth2Validator;

use Illuminate\Support\ServiceProvider;

/**
 * Class AzureOauth2ValidatorServiceProvider.
 *
 * @package Yayasanvitka\AzureOauth2Validator
 */
class AzureOauth2ValidatorServiceProvider extends ServiceProvider
{
    /**
     * Boot the application.
     */
    public function boot()
    {
        $this->offerPublishing();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/azure-oauth2-validator.php',
            'azure-oauth2-validator'
        );
    }

    /**
     * Only publish if `config_path` function is available.
     * It would not offer publishing in Lumen.
     *
     * @return void
     */
    protected function offerPublishing()
    {
        if (function_exists('config_path')) {
            $this->publishes([
                __DIR__.'/../config/azure-oauth2-validator.php' => config_path('azure-oauth2-validator.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../database/migrations/create_user_web_tokens_table.php' => now()->format('Y_m_d_His_').'create_permission_tables.php',
            ], 'migrations');
        }
    }
}
