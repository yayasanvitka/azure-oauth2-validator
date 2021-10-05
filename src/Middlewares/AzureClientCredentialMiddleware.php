<?php

namespace Yayasanvitka\AzureOauth2Validator\Middlewares;

use Closure;
use Illuminate\Validation\UnauthorizedException;
use Yayasanvitka\AzureOauth2Validator\AzureOauth2ClientCredentialValidator;

/**
 * Class AzureClientCredentialMiddleware.
 *
 * @package Yayasanvitka\AzureOauth2Validator\Middlewares
 */
class AzureClientCredentialMiddleware
{
    public function handle($request, Closure $next)
    {
        if (blank($request->bearerToken())) {
            throw new UnauthorizedException('Token is not supplied', 401);
        }

        try {
            (new AzureOauth2ClientCredentialValidator($request->bearerToken()))->validate();
        } catch (\Exception $exception) {
            throw new UnauthorizedException($exception->getMessage(), 403, $exception);
        }

        return $next($request);
    }
}
