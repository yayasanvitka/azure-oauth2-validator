# Azure OAuth 2 JWT Validator for Laravel
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)
![GitHub Workflow Status (event)](https://img.shields.io/github/workflow/status/yayasanvitka/azure-oauth2-validator/PHPUnit%20Tests)
[![PHP Version](https://badgen.net/packagist/php/yayasanvitka/azure-oauth2-validator)](https://www.php.net/releases/8.0/en.php)
[![Latest release](https://badgen.net/packagist/v/yayasanvitka/azure-oauth2-validator)](https://github.com/yayasanvitka/azure-oauth2-validator)
[![codecov](https://badgen.net/codecov/c/github/yayasanvitka/azure-oauth2-validator?token=QQFKRA9YA8)](https://codecov.io/gh/yayasanvitka/azure-oauth2-validator)

## About

This package does OAuth2 token validation. **For now, it only validates client credentials**. 

## Documentation, Installation, and Usage Instructions

### Installation
#### 1. Install the Package
Run the following command to install the package:
```bash
composer require yayasanvitka/azure-oauth2-validator
composer require rootinc/laravel-azure-middleware
```

#### 2. Publish the Package
Run the following command to publish the package:
```bash
php artisan vendor:publish --provider="Yayasanvitka\AzureOauth2Validator\AzureOauth2ValidatorServiceProvider"
```
Then run command below to migrate published table.
```bash
php artisan migrate
```

#### 3. Add Configurations to Database Seeder
Add the following array to `Database/Seeders/ConfigTableSeeder@SettingList`:
```php
[
    'key' => 'system.employee.allowed_domains',
    'name' => 'Allowed domain to login',
    'description' => '',
    'value' => '[{"domain":"btp.ac.id"},{"domain":"iteba.ac.id"},{"domain":"yayasanvitka.id"}]',
    'field' => '{"name":"value","label":"Value","type":"repeatable","fields":[{"name":"domain","type":"text","label":"Domain"}]}',
    'active' => 1,
    'created_at' => now('Asia/Jakarta'),
    'updated_at' => now('Asia/Jakarta'),
],
[
    'key' => 'azure.client.id',
    'name' => 'Azure OAuth2 Application (client) ID (UUID)',
    'description' => 'Application (client) ID (UUID) for Azure Authentication',
    'value' => '',
    'field' => '{"name":"value","label":"Azure OAuth2 Application (client) ID","type":"text"}',
    'active' => 1,
    'created_at' => now('Asia/Jakarta'),
    'updated_at' => now('Asia/Jakarta'),
],
[
    'key' => 'azure.client.secret',
    'name' => 'Azure OAuth2 Application (client) Secret',
    'description' => 'Application (client) Secret for Azure Authentication',
    'value' => '',
    'field' => '{"name":"value","label":"Azure OAuth2 Application (client) Secret","type":"text"}',
    'active' => 1,
    'created_at' => now('Asia/Jakarta'),
    'updated_at' => now('Asia/Jakarta'),
],
[
    'key' => 'azure.tenant_id',
    'name' => 'Directory (tenant) ID (UUID)',
    'description' => 'Directory (tenant) ID (UUID) for Azure Authentication',
    'value' => '',
    'field' => '{"name":"value","label":"Directory (tenant) ID","type":"text"}',
    'active' => 1,
    'created_at' => now('Asia/Jakarta'),
    'updated_at' => now('Asia/Jakarta'),
],
[
    'key' => 'azure.resource',
    'name' => 'Azure OAuth2 Resource',
    'description' => 'Valid resource to authenticate to Azure',
    'value' => '',
    'field' => '{"name":"value","label":"Resource","type":"text"}',
    'active' => 1,
    'created_at' => now('Asia/Jakarta'),
    'updated_at' => now('Asia/Jakarta'),
],
[
    'key' => 'azure.scope',
    'name' => 'Azure OAuth2 Scope',
    'description' => 'Valid scope to authenticate to Azure',
    'value' => '',
    'field' => '{"name":"value","label":"Scope","type":"text"}',
    'active' => 1,
    'created_at' => now('Asia/Jakarta'),
    'updated_at' => now('Asia/Jakarta'),
]
```
Then run command below to seed the new configuration.
```bash
php artisan db:seed --class=ConfigTableSeeder
```
And dont forget to register the azure config at `App\Providers\ConfigServiceProvider@overrideConfigValues`

> **Note:** You may need to log in to the app as a sysadmin (non-Microsoft account) first to ensure the config is loaded.

#### 4. Add Routes for Azure Authentication
Add the following routes to `routes/azure.php`:
```php
<?php

use App\Http\Middleware\AppAzureMiddleware;

Route::get('/login/azure', [AppAzureMiddleware::class, 'azure'])->name('auth.azure');
Route::get('/login/azurecallback', [AppAzureMiddleware::class, 'azurecallback'])->name('auth.azurecallback');
Route::get('/logout/azure', [AppAzureMiddleware::class, 'azurelogout'])->name('auth.logout');
```

#### 5. Register Azure Routes
Add the following code to `bootstrap/app.php` to register the Azure routes:
```php
Route::middleware('web')
    ->group(base_path('routes/azure.php'));
```

#### 6. Implement Middleware for Azure Authentication
Create a file `app/Http/Middleware/AppAzureMiddleware.php` and add the following content:
```php
<?php

namespace App\Http\Middleware;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Closure;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\UnauthorizedException;
use Prologue\Alerts\Facades\Alert;
use RootInc\LaravelAzureMiddleware\Azure;
use Yayasanvitka\AzureOauth2Validator\WebToken;

class AppAzureMiddleware extends Azure
{
    public function handle($request, Closure $next)
    {
        $webToken = new WebToken($request->user(), $request->getClientIp());

        try {
            $webToken->validateUserToken();
        } catch (Exception $exception) {
            Alert::error($exception->getMessage())->flash();

            return $this->redirect($request);
        }

        return $next($request);
    }

    protected function redirect(Request $request)
    {
        auth()->logout();

        return redirect()->guest($this->login_route);
    }

    protected function success(Request $request, $access_token, $refresh_token, $profile): mixed
    {
        try {
            $user = activity()->withoutLogs(function () use ($profile, $request) {
                $user = User::updateOrCreate(
                    [
                        'email' => $profile->upn,
                        'uuid' => $profile->oid,
                    ],
                    [
                        'name' => trim($profile->name),
                        'password' => bcrypt(Str::random(18)),
                        'last_login_ip' => $profile->ipaddr,
                        'last_login_at' => now()->toDateTimeString(),
                        'azure_user' => true,
                    ]
                );

                if (User::all() == null) {
                    $user->roles()->sync(1);
                }

                Auth::login($user, true);

                (new WebToken(
                    $user,
                    $request->getClientIp()
                ))->storeAuthorizedUserTokens();

                if (app()->environment('local') && User::count() == 1) {
                    $user->roles()->sync(Role::first()->id);
                }

                return $user;
            });

            activity('access')->log('Login')->causedBy($user);
        } catch (Exception $exception) {
            Alert::error($exception->getMessage())->flash();

            return $this->redirect($request);
        }

        return parent::success($request, $access_token, $refresh_token, $profile);
    }

    public function azurecallback(Request $request)
    {
        $client = new Client();

        $code = $request->input('code');

        try {
            $response = $client->request('POST', $this->baseUrl.config('azure.tenant_id').$this->route.'token', [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'client_id' => config('azure.client.id'),
                    'client_secret' => config('azure.client.secret'),
                    'code' => $code,
                    'resource' => config('azure.resource'),
                ],
            ]);

            $contents = json_decode($response->getBody()->getContents());
        } catch (RequestException $e) {
            return $this->fail($request, $e);
        }

        $profile = json_decode(base64_decode(explode('.', $contents->id_token)[1]));

        if (! $this->validateDomains($profile->upn)) {
            return $this->fail($request, new UnauthorizedException('You are not allowed to logon to this app!', 401));
        }

        session()->put('_rootinc_azure_access_token', $contents->access_token);
        session()->put('_rootinc_azure_refresh_token', $contents->refresh_token);

        (new WebToken(new User(), $request->getClientIp()))->storeTokens($contents);

        return $this->success($request, $contents->access_token, $contents->refresh_token, $profile);
    }

    private function validateDomains(string $email): bool
    {
        [, $domain] = explode('@', $email);

        if (! in_array($domain, Setting::allowedDomains())) {
            return false;
        }

        return true;
    }
}
```

#### 7. Update `Setting` Model
Add the following method to the `Setting` model:
```php
public static function allowedDomains(): ?array
{
    return collect(json_decode(config('system.employee.allowed_domains'), true))
        ->pluck('domain')
        ->toArray();
}
```

#### 8. Update `User` Model
Add this method to define the relation with user web tokens:
```php
public function webTokens(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(AzureWebToken::class, 'user_id', 'id')
        ->where('revoked', 0);
}
```

### Documentation
<small>WIP</small>

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information.

### Security

If you discover any security-related issues, please email [adly@yayasanvitka.id](mailto:adly@yayasanvitka.id) instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
