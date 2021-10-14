<?php

use Carbon\Carbon;
use Yayasanvitka\AzureOauth2Validator\AzureOauth2ClientCredentialValidator;
use Yayasanvitka\AzureOauth2Validator\Exceptions\AzureTokenException;

uses(\Yayasanvitka\AzureOauth2Validator\Test\TestCase::class);
uses(\Yayasanvitka\AzureOauth2Validator\Test\TestHelperTrait::class);

it('can initialize the validator', function () {
    $token = $this->getTokenForTesting();
    $accessToken = $token['access_token'];

    $instance = new AzureOauth2ClientCredentialValidator(token: $accessToken);
    expect($instance)->toBeInstanceOf(AzureOauth2ClientCredentialValidator::class);
})->group('client_credentials');

it('will throw error when supplied with invalid token', function () {
    $instance = new AzureOauth2ClientCredentialValidator(token: 'abc.1');
})->throws(AzureTokenException::class)->group('client_credentials');

it('will throw error when supplied with invalid token claims', function () {
    $instance = (new AzureOauth2ClientCredentialValidator(token: 'abc.1.claim'))->getClaim();
})->throws(AzureTokenException::class)->group('client_credentials');

it('can validate the token', function () {
    $token = $this->getTokenForTesting();

    expect((new AzureOauth2ClientCredentialValidator(token: $token['access_token']))->validate())
        ->toBeTrue();
})->group('client_credentials');

it('throws an error when the tenant id does not match', function () {
    $token = $this->getTokenForTesting();
    config(['azure-oauth2-validator.tenant_id' => \Illuminate\Support\Str::uuid()->toString()]);
    (new AzureOauth2ClientCredentialValidator(token: $token['access_token']))->validate();
})->throws(AzureTokenException::class)->group('client_credentials');

it('throws an error when the expected audience does not match', function () {
    $token = $this->getTokenForTesting();
    config(['azure-oauth2-validator.valid_aud' => [\Illuminate\Support\Str::uuid()->toString()]]);
    (new AzureOauth2ClientCredentialValidator(token: $token['access_token']))->validate();
})->throws(AzureTokenException::class)->group('client_credentials');

it('throws an error when the token is expired', function () {
    $token = $this->getTokenForTesting();
    Carbon::setTestNow(now()->addHours(2));
    (new AzureOauth2ClientCredentialValidator(token: $token['access_token']))->validate();
})->throws(AzureTokenException::class)->group('client_credentials');

it('throws an error when signature verification fails', function () {
    // mock the object
    $mock = mock(AzureOauth2ClientCredentialValidator::class)->makePartial();
    $mock->shouldReceive('validateSign')->andReturnFalse();
    $mock->validate();
})->throws(AzureTokenException::class)->group('client_credentials');

it('throws an error when appid verification fails', function () {
    $token = $this->getTokenForTesting();
    config(['azure-oauth2-validator.validates_app_id' => true]);
    (new AzureOauth2ClientCredentialValidator(token: $token['access_token']))->validate();
})->throws(AzureTokenException::class)->group('client_credentials');
