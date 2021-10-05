<?php

use Illuminate\Validation\UnauthorizedException;

uses(\Yayasanvitka\AzureOauth2Validator\Test\TestCase::class);
uses(\Yayasanvitka\AzureOauth2Validator\Test\TestHelperTrait::class);

it('throws unauthorized exception when no bearer token supplied', function () {
    $request = new \Illuminate\Http\Request();

    $middleware = new \Yayasanvitka\AzureOauth2Validator\Middlewares\AzureClientCredentialMiddleware();

    $middleware->handle($request, fn () => (''));
})->throws(UnauthorizedException::class)->group('middleware');

it('throws unauthorized exception when invalid token is supplied', function () {
    $token = $this->getTokenForTesting();
    $accessToken = $token['access_token'].'asdqwe123456';

    $request = new \Illuminate\Http\Request();
    $request->headers->set('Authorization', "Bearer {$accessToken}");

    $middleware = new \Yayasanvitka\AzureOauth2Validator\Middlewares\AzureClientCredentialMiddleware();
    $middleware->handle($request, fn () => (''));
})->throws(UnauthorizedException::class)->group('middleware');

it('return the request for next middleware', function () {
    $token = $this->getTokenForTesting();
    $accessToken = $token['access_token'];

    $request = new \Illuminate\Http\Request();
    $request->headers->set('Authorization', "Bearer {$accessToken}");

    $middleware = new \Yayasanvitka\AzureOauth2Validator\Middlewares\AzureClientCredentialMiddleware();
    $next = $middleware->handle($request, function ($request) {
        return $request;
    });

    \PHPUnit\Framework\assertEquals($request, $next);
})->group('middleware');
