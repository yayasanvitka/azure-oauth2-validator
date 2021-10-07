<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tenant ID
    |--------------------------------------------------------------------------
    |
    | Directory (tenant) ID (GUID) of your Microsoft Azure.
    |
    */
    'tenant_id' => env('AZURE_OAUTH2_VALIDATOR_TENANT_ID', null),

    /*
    |--------------------------------------------------------------------------
    | Audience
    |--------------------------------------------------------------------------
    |
    | Valid audience (aud) is the intended recipient of the token that we want
    | to validate. You can fill this with comma separated values.
    |
    | This value is usually the Client ID of your azure app registration.
    |
    */
    'valid_aud' => explode(',', env('AZURE_OAUTH2_VALIDATOR_VALID_AUD')) ?? [],

    'web_token' => [

        /*
        |--------------------------------------------------------------------------
        | Azure OAuth2 Web Token Client ID
        |--------------------------------------------------------------------------
        */
        'client_id' => env('AZURE_OAUTH2_VALIDATOR_WEB_TOKEN_CLIENT_ID', null),

        /*
        |--------------------------------------------------------------------------
        | Azure OAuth2 Web Token Client Secret
        |--------------------------------------------------------------------------
        */
        'client_secret' => env('AZURE_OAUTH2_VALIDATOR_WEB_TOKEN_CLIENT_SECRET', null),

        /*
        |--------------------------------------------------------------------------
        | Azure OAuth2 Web Token Resource
        |--------------------------------------------------------------------------
        */
        'resource' => env('AZURE_OAUTH2_VALIDATOR_WEB_TOKEN_RESOURCE', 'https://graph.microsoft.com'),
    ],
];
