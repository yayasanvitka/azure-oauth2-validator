<?php

namespace Yayasanvitka\AzureOauth2Validator;

use Carbon\Carbon;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Yayasanvitka\AzureOauth2Validator\Exceptions\AzureOauth2ValidationException;

/**
 * Class AzureCommonKey.
 *
 * @package Yayasanvitka\AzureOauth2Validator
 */
class AzureCommonKey
{
    public string $url = 'https://login.microsoftonline.com/consumers/discovery/v2.0/keys';
    protected FilesystemAdapter $disk;

    /**
     * @param string|null $tenantId
     * @param string $diskName
     *
     * @throws \Yayasanvitka\AzureOauth2Validator\Exceptions\AzureOauth2ValidationException
     */
    public function __construct(
        string $tenantId = null,
        string $diskName = 'local',
    ) {
        if (blank($tenantId)) {
            throw AzureOauth2ValidationException::tenantIdIsEmpty();
        }

        $this->url = str_replace('consumers', $tenantId, $this->url);
        $this->disk = Storage::disk($diskName);
    }

    /**
     * Create OpenSSL Certificate based on selected common key.
     *
     * @param $keyIndex
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \Illuminate\Http\Client\RequestException
     *
     * @return mixed
     */
    public function getCertificateForValidation($keyIndex)
    {
        $index = $this->getKeys($keyIndex);
        $string_certText = "-----BEGIN CERTIFICATE-----\r\n".chunk_split($index['x5c'][0], 64)."-----END CERTIFICATE-----\r\n";

        return $this->getPublicKeyFromX5C($string_certText);
    }

    /**
     * @param $string_certText
     *
     * @return mixed
     */
    private function getPublicKeyFromX5C($string_certText)
    {
        $object_cert = openssl_x509_read($string_certText);
        $object_pubkey = openssl_pkey_get_public($object_cert);
        $array_publicKey = openssl_pkey_get_details($object_pubkey);

        return $array_publicKey['key'];
    }

    /**
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function downloadKeys()
    {
        $jsonResponse = \Illuminate\Support\Facades\Http::timeout(10)
            ->get($this->url)
            ->throw()
            ->json();

        $this->saveCommonKeys($this->addTimestampToResponse($jsonResponse));
    }

    /**
     * @param array $jsonResponse
     *
     * @return string
     */
    private function addTimestampToResponse(array $jsonResponse): string
    {
        $jsonResponse['timestamp'] = now('Asia/Jakarta')->timestamp;

        return json_encode($jsonResponse);
    }

    /**
     * Save Common Key to JSON file.
     *
     * @param string $text_to_write
     *
     * @return void
     */
    private function saveCommonKeys(string $text_to_write)
    {
        $this->disk->put('azure_common_key.json', $text_to_write);
    }

    /**
     * @param null $keyIndex
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \Illuminate\Http\Client\RequestException
     *
     * @return array
     */
    public function getKeys($keyIndex = null): array
    {
        if ($this->disk->has('azure_common_key.json')) {
            $data = json_decode($this->disk->get('azure_common_key.json'), true);
            $timestamp = $data['timestamp'];
            if (now()->diffInDays(Carbon::createFromTimestamp($timestamp)) > 0) {
                $this->downloadKeys();

                return $this->getKeys($keyIndex);
            }

            if (!empty($keyIndex)) {
                foreach ($data['keys'] as $val) {
                    if ($val['kid'] == $keyIndex) {
                        return $val;
                    }
                }
            }

            return $data['keys'];
        }

        $this->downloadKeys();

        return $this->getKeys($keyIndex);
    }
}
