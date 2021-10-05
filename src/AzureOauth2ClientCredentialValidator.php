<?php

namespace Yayasanvitka\AzureOauth2Validator;

use StdClass;
use Yayasanvitka\AzureOauth2Validator\Exceptions\AzureTokenException;
use Yayasanvitka\AzureOauth2Validator\Traits\AzureOAuth2ValidatorTrait;

/**
 * Class AzureOauth2ClientCredentialValidator.
 *
 * @package Yayasanvitka\AzureOauth2Validator
 */
class AzureOauth2ClientCredentialValidator
{
    use AzureOAuth2ValidatorTrait;

    protected StdClass $claim;
    protected string $header_enc;
    protected string $claim_enc;
    protected string $signature_enc;

    /**
     * @param string $token
     * @param ?string $tenantId
     * @param string $disk
     *
     * @throws \Yayasanvitka\AzureOauth2Validator\Exceptions\AzureTokenException
     */
    public function __construct(
        public string $token,
        public ?string $tenantId = null,
        public string $disk = 'local',
    ) {
        if (blank($this->tenantId)) {
            $this->tenantId = config('azure-oauth2-validator.tenant_id');
        }

        try {
            list($this->header_enc, $this->claim_enc, $this->signature_enc) = explode('.', $token);
            $this->getClaim();
        } catch (\Exception $exception) {
            throw new AzureTokenException('Invalid or Malformed Token', 'T_INV_TOKEN');
        }
    }

    /**
     * @return StdClass|array
     */
    public function getHeader(): StdClass|array
    {
        return json_decode($this->base64_url_decode($this->header_enc));
    }

    /**
     * @throws \Yayasanvitka\AzureOauth2Validator\Exceptions\AzureTokenException
     * @return \StdClass
     */
    public function getClaim(): StdClass
    {
        if (!isset($this->claim)) {
            $this->claim = json_decode($this->base64_url_decode($this->claim_enc));
        }

        if (!$this->claim instanceof StdClass) {
            throw new AzureTokenException('Invalid Token', 'T_INV_TOKEN');
        }

        return $this->claim;
    }

    /**
     * @throws AzureTokenException
     *
     * @return string
     */
    public function getAudience(): string
    {
        $claim = $this->getClaim();
        list(, $aud) = explode('://', $claim->aud);

        return $aud;
    }

    /**
     * @return false|string
     */
    public function getSignature(): false|string
    {
        return $this->base64_url_decode($this->signature_enc);
    }

    /**
     * @throws \Yayasanvitka\AzureOauth2Validator\Exceptions\AzureTokenException
     */
    public function validate(): bool
    {
        // validate signature with Azure Common Keys
        if (!$this->validateSign()) {
            throw new AzureTokenException('Invalid Signature', 'T_INV_SIGN');
        }

        // validate Tenant ID
        if ($this->claim->tid !== $this->tenantId) {
            throw new AzureTokenException('Invalid Tenant ID', 'T_INV_TID');
        }

        // validate timestamp
        if (!$this->validateTimestamp()) {
            throw new AzureTokenException('Invalid Timestamp', 'T_INV_TIMESTAMP');
        }

        // validate Audience
        if (!in_array($this->getAudience(), config('azure-oauth2-validator.valid_aud'))) {
            throw new AzureTokenException('Invalid Audience', 'T_INV_AUD');
        }

        return true;
    }

    /**
     * @throws \Yayasanvitka\AzureOauth2Validator\Exceptions\AzureTokenException
     *
     * @return bool
     */
    public function validateTimestamp(): bool
    {
        $now = now('Asia/Jakarta')->timestamp;

        return match (true) {
            $this->getClaim()->iat > $now, $this->getClaim()->nbf > $now, $this->getClaim()->exp < $now => false,
            default => true,
        };
    }

    /**
     * Validate Signature.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \Yayasanvitka\AzureOauth2Validator\Exceptions\AzureOauth2ValidationException
     *
     * @return bool
     */
    public function validateSign(): bool
    {
        $header = $this->getHeader();
        $certs = (new AzureCommonKey(tenantId: $this->tenantId, diskName: $this->disk))->getCertificateForValidation($header->kid);

        return openssl_verify($this->header_enc.'.'.$this->claim_enc, $this->getSignature(), $certs, 'RSA-SHA256');
    }

}
