<?php

namespace Yayasanvitka\AzureOauth2Validator\Test;

use Illuminate\Support\Facades\Http;

/**
 * Trait TestHelperTrait.
 *
 * @package Yayasanvitka\AzureOauth2Validator\Test
 */
trait TestHelperTrait
{
    /**
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function getTokenForTesting()
    {
        $url = 'https://login.microsoftonline.com/'.env('TEST_TENANT_UUID').'/oauth2/v2.0/token';
        $clientId = env('TEST_CLIENT_UUID');
        $clientSecret = env('TEST_CLIENT_SECRET');
        $scope = env('TEST_CLIENT_SCOPE');

        return Http::asForm()
            ->post($url, [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'scope' => $scope,
                'grant_type' => 'client_credentials',
            ])->throw()->json();
    }
}
