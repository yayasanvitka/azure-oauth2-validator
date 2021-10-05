<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cache Tag
    |--------------------------------------------------------------------------
    |
    | This value is the cache tag name that will be used to store the JWT token.
    |
    */
    'cache_tag' => env('CLIENT_CREDENTIALS_CACHE_TAG', 'jwt_client_credentials'),

    /*
    |--------------------------------------------------------------------------
    | Cache Lifetime
    |--------------------------------------------------------------------------
    |
    | Default cache time from Microsoft is 3600 seconds (1 Hour). But for safety reason,
    | we are limiting it to 3000s (50 minutes).
    |
    */
    'cache_lifetime' => 3000,

    /*
    |--------------------------------------------------------------------------
    | Authentication URL
    |--------------------------------------------------------------------------
    |
    | Microsoft default authentication URL.
    |
    */
    'auth_url' => 'https://login.microsoftonline.com',

    /*
    |--------------------------------------------------------------------------
    | Authentication Endpoint
    |--------------------------------------------------------------------------
    |
    | Microsoft default authentication Endpoint.
    |
    */
    'auth_endpoint' => '/oauth2/v2.0/token',

    /*
    |--------------------------------------------------------------------------
    | Tenant ID
    |--------------------------------------------------------------------------
    |
    | Directory (tenant) ID (GUID) of your Microsoft Azure.
    |
    */
    'tenant_id' => env('CLIENT_CREDENTIALS_TENANT_ID', null),

    /*
    |--------------------------------------------------------------------------
    | Client ID
    |--------------------------------------------------------------------------
    |
    | Application (client) ID (GUID) of your App Registration on Microsoft Azure.
    |
    */
    'client_id' => env('CLIENT_CREDENTIALS_CLIENT_ID', null),

    /*
    |--------------------------------------------------------------------------
    | Client Secret
    |--------------------------------------------------------------------------
    |
    | We are using shared secret to authenticate against the endpoint.
    | For documentation on how to create the App Registration shared secret,
    | please refer to https://docs.microsoft.com/en-us/azure/active-directory/develop/v2-oauth2-client-creds-grant-flow#get-a-token
    |
    */
    'client_secret' => env('CLIENT_CREDENTIALS_SECRET', null),

    /*
    |--------------------------------------------------------------------------
    | Scope
    |--------------------------------------------------------------------------
    |
    | The scope of your JWT Token.
    | By default, it will be set to https://graph.microsoft.com/.default
    | If you need to set this value, fill it with Application (client) GUID of your target App Registration.
    | For example, if you have Application GUID of '8688072f-b12d-4f26-b264-2f27fcc0cd23', set it to this value.
    | It will then format it to 'api://8688072f-b12d-4f26-b264-2f27fcc0cd23/.default'
    |
    | Before using it, make sure to 'Expose an API' on target App Registration, and modify the manifest so it can be used as Application Permission
    | and set it on the App Registration that is used as 'client_id' as 'API Permission' with 'Application Permission'.
    |
    */
    'scope' => env('CLIENT_CREDENTIALS_SCOPE', 'https://graph.microsoft.com/.default'),
];
