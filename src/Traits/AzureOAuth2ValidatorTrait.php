<?php

namespace Yayasanvitka\AzureOauth2Validator\Traits;

/**
 * Trait AzureOAuth2ValidatorTrait.
 *
 * @package Yayasanvitka\AzureOauth2Validator\Traits
 */
trait AzureOAuth2ValidatorTrait
{
    /**
     * @param string $arg
     *
     * @return false|string
     */
    public function base64_url_decode(string $arg): false|string
    {
        $arg = str_replace('-', '+', $arg);
        $arg = str_replace('_', '/', $arg);
        switch (strlen($arg) % 4) {
            case 2:
                $arg .= '==';

                break;
            case 3:
                $arg .= '=';

                break;
            default:
                break;
        }

        return base64_decode($arg);
    }
}
