<?php

namespace Yayasanvitka\AzureOauth2Validator;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Yayasanvitka\AzureOauth2Validator\Exceptions\AzureTokenException;
use Yayasanvitka\AzureOauth2Validator\Models\AzureWebToken;

/**
 * Class WebToken.
 *
 * @package Yayasanvitka\AzureOauth2Validator
 */
class WebToken
{
    public string $baseUrl = 'https://login.microsoftonline.com/';
    public string $route = '/oauth2/';

    public function __construct(
        public \App\Models\User $user,
        public string $ipAddress,
    ) {
    }

    /**
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \Yayasanvitka\AzureOauth2Validator\Exceptions\AzureTokenException
     *
     * @return true
     */
    public function validateUserToken(): bool
    {
        $tokenExpires = $this->getCurrentUserTokenExpiry();

        if ($tokenExpires <= now('Asia/Jakarta')) {
            $this->refresh();
        }

        return true;
    }

    /**
     * @return \Yayasanvitka\AzureOauth2Validator\Models\AzureWebToken|null
     */
    private function getCurrentUserTokenFromDB(): ?AzureWebToken
    {
        return $this->user->webTokens()
            ->where('revoked', false)
            ->where('session_id', session()->getId())
            ->latest()
            ->first();
    }

    /**
     * Get the user's ID Token.
     * If it doesn't exist on session, get from database instead.
     *
     * @return string|null
     */
    public function getCurrentUserIdToken(): ?string
    {
        return empty(session('idToken'))
            ? $this->getRefreshTokenFromDB()
            : session('idToken');
    }

    /**
     * Get the user's Refresh Token.
     * If it doesn't exist on session, get from database instead.
     *
     * @return string|null
     */
    public function getCurrentUserRefreshToken(): ?string
    {
        return empty(session('refreshToken'))
            ? $this->getRefreshTokenFromDB()
            : session('refreshToken');
    }

    /**
     * Get the user's token expiration.
     * If it doesn't exist on session, get from database instead.
     * Should always return in \Carbon\Carbon format.
     *
     * @return \Carbon\Carbon
     */
    public function getCurrentUserTokenExpiry(): Carbon
    {
        $expires = empty(session('tokenExpires')) ? $this->getTokenExpiryFromDB() : session('tokenExpires');

        // format to carbon
        if (!$expires instanceof Carbon) {
            $expires = Carbon::createFromTimestamp($expires, 'Asia/Jakarta');
        }

        return $expires;
    }

    /**
     * Get Stored ID Token.
     *
     * @return string|null
     */
    public function getIdTokenFromDB(): ?string
    {
        $token = $this->getCurrentUserTokenFromDB();

        return ($token instanceof AzureWebToken) ? $token->id_token : null;
    }

    /**
     * Get Stored Refresh Token.
     *
     * @return string|null
     */
    public function getRefreshTokenFromDB(): ?string
    {
        $token = $this->getCurrentUserTokenFromDB();

        return ($token instanceof AzureWebToken) ? $token->expires_at : null;
    }

    /**
     * @return \Carbon\Carbon|null
     */
    public function getTokenExpiryFromDB(): ?Carbon
    {
        $token = $this->getCurrentUserTokenFromDB();

        return ($token instanceof AzureWebToken) ? $token->expires_at : null;
    }

    /**
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \Yayasanvitka\AzureOauth2Validator\Exceptions\AzureTokenException
     */
    public function refresh()
    {
        $response = Http::asForm()
            ->post($this->baseUrl.config('azure-oauth2-validator.tenant_id').$this->route.'token', [
                'grant_type' => 'refresh_token',
                'client_id' => config('azure-oauth2-validator.web_token.client_id'),
                'client_secret' => config('azure-oauth2-validator.web_token.client_secret'),
                'refresh_token' => $this->getCurrentUserRefreshToken(),
                'resource' => config('azure-oauth2-validator.web_token.resource'),
            ])->throw(function ($response, $e) {
                $jsonError = $response->json();
                if (!blank($jsonError)) {
                    throw new AzureTokenException("[{$jsonError['error']}] {$jsonError['error_description']}", $jsonError['error_codes'][0], $e);
                }
            })->object();

        if (empty($response->access_token) || empty($response->refresh_token)) {
            throw new AzureTokenException('Missing tokens in response contents', 500);
        }

        $this->updateTokens($response);
    }

    /**
     * Store User Web Token on database.
     */
    public function storeAuthorizedUserTokens()
    {
        $this->user->webTokens()->updateOrCreate([
            'session_id' => session()->getId(),
            'revoked' => 0,
        ], [
            'ip_address' => $this->ipAddress,
            'access_token' => session()->get('accessToken'),
            'refresh_token' => session()->get('refreshToken'),
            'id_token' => session()->get('idToken'),
            'expires_at' => session()->get('tokenExpires'),
            'revoked' => 0,
        ]);
    }

    /**
     * Revoke stored tokens.
     */
    public function revokeAuthorizedUserTokens()
    {
        $this->user->webTokens()
            ->where('session_id', session()->getId())
            ->update([
                'revoked' => true,
            ]);
    }

    /**
     * Store Tokens.
     *
     * @param $contents
     */
    public function storeTokens($contents)
    {
        session([
            'accessToken' => $contents->access_token,
            'refreshToken' => $contents->refresh_token,
            'tokenExpires' => $contents->expires_on,
            'idToken' => $contents->id_token,
        ]);
    }

    /**
     * Store Tokens.
     *
     * @param $contents
     */
    public function updateTokens($contents)
    {
        session([
            'accessToken' => $contents->access_token,
            'refreshToken' => $contents->refresh_token,
            'tokenExpires' => $contents->expires_on,
            'idToken' => $this->getCurrentUserIdToken(),
        ]);

        $this->storeAuthorizedUserTokens();
    }

    /**
     * Clear Tokens.
     */
    public function clearTokens()
    {
        session()->forget('accessToken');
        session()->forget('refreshToken');
        session()->forget('tokenExpires');
        session()->forget('idToken');

        $this->revokeAuthorizedUserTokens();
    }
}
