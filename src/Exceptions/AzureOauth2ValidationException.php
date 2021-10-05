<?php

namespace Yayasanvitka\AzureOauth2Validator\Exceptions;

use JetBrains\PhpStorm\Pure;

/**
 * Class AzureOauth2ValidationException.
 *
 * @package Yayasanvitka\AzureOauth2Validator\Exceptions
 */
class AzureOauth2ValidationException extends \Exception
{
    /**
     * Thrown when empty Tenant ID detected.
     *
     * @return static
     */
    #[Pure]
    public static function tenantIdIsEmpty(): self
    {
        return new static('Tenant ID is Empty');
    }

    /**
     * Thrown when empty Tenant ID detected.
     *
     * @return static
     */
    #[Pure]
    public static function invalidClaim(): self
    {
        return new static('Invalid Claim. Token might be invalid or malformed.');
    }
}
