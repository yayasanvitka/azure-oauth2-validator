<?php

namespace Yayasanvitka\AzureOauth2Validator\Test;

use Yayasanvitka\AzureOauth2Validator\AzureOauth2ValidatorServiceProvider;

/**
 * Class TestCase.
 *
 * @package Yayasanvitka\AzureOauth2Validator\Test
 */
class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            AzureOauth2ValidatorServiceProvider::class,
        ];
    }
}
