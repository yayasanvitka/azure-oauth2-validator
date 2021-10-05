<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yayasanvitka\AzureOauth2Validator\AzureCommonKey;

uses(\Yayasanvitka\AzureOauth2Validator\Test\TestCase::class);

test('it throws error when creating instance without tenant id', function () {
    expect(blank(''))->toBeTrue();
    new AzureCommonKey();
})->throws(\Yayasanvitka\AzureOauth2Validator\Exceptions\AzureOauth2ValidationException::class)->group('common_key');

test('it throws error when invalid disk is supplied', function () {
    new AzureCommonKey(tenantId: Str::uuid()->toString(), diskName: 'testing');
})->throws(\InvalidArgumentException::class)->group('common_key');

test('it can be initialized with correct params', function () {
    $commonKey = new AzureCommonKey(tenantId: Str::uuid()->toString(), diskName: 'local');
    expect($commonKey)->toBeInstanceOf(AzureCommonKey::class);
})->group('common_key');

test('it throws error when trying to download with invalid tenantId', function () {
    $commonKey = new AzureCommonKey(tenantId: Str::uuid()->toString(), diskName: 'local');
    $commonKey->downloadKeys();
})->throws(\Illuminate\Http\Client\RequestException::class)->group('common_key');

test('it can download common key from microsoft', function () {
    $commonKey = new AzureCommonKey(tenantId: config('azure-oauth2-validator.tenant_id'), diskName: 'local');
    $commonKey->downloadKeys();
    $this->assertTrue(Storage::disk('local')->exists('azure_common_key.json'));
})->group('common_key');

test('it can cache the common keys', function () {
    $commonKey = new AzureCommonKey(tenantId: Str::uuid()->toString(), diskName: 'local');
    $keys = $commonKey->getKeys();

    $this->assertIsArray($keys);
})->group('common_key');

test('it can redownload the common keys if it does not exists', function () {
    Storage::disk('local')->delete('azure_common_key.json');
    $commonKey = new AzureCommonKey(tenantId: config('azure-oauth2-validator.tenant_id'), diskName: 'local');
    $keys = $commonKey->getKeys();

    $this->assertIsArray($keys);
})->group('common_key');

test('it can redownload the common keys if last download is more than one day', function () {
    $disk = Storage::disk('local');

    // make sure the file does not exists
    $disk->delete('azure_common_key.json');

    // redownload the key
    $commonKey = new AzureCommonKey(tenantId: config('azure-oauth2-validator.tenant_id'), diskName: 'local');
    $commonKey->downloadKeys();

    // modify the timestamp
    $data = json_decode(Storage::disk('local')->get('azure_common_key.json'), true);
    $data['timestamp'] = now()->subDays(3);
    $disk->put('azure_common_key.json', json_encode($data));

    // redownload the keys
    $keys = $commonKey->getKeys();

    $this->assertIsArray($keys);
})->group('common_key');

test('it can return key based on kid index', function () {
    $disk = Storage::disk('local');

    // make sure the file does not exists
    $disk->delete('azure_common_key.json');

    // redownload the key
    $commonKey = new AzureCommonKey(tenantId: config('azure-oauth2-validator.tenant_id'), diskName: 'local');
    $commonKey->downloadKeys();

    $data = json_decode(Storage::disk('local')->get('azure_common_key.json'), true);
    $firstKey = $data['keys'][0]['kid'];
    $keys = $commonKey->getKeys($firstKey);

    $this->assertIsArray($keys);
    $this->assertArrayHasKey('kid', $keys);
    $this->assertArrayHasKey('n', $keys);
    $this->assertArrayHasKey('issuer', $keys);
    $this->assertArrayHasKey('x5c', $keys);
})->group('common_key');
