<?php

namespace Yayasanvitka\AzureOauth2Validator\Exceptions;

use JetBrains\PhpStorm\Pure;
use Throwable;

/**
 * Class AzureTokenException.
 *
 * @package Yayasanvitka\AzureOauth2Validator\Exceptions
 */
class AzureTokenException extends \Exception
{
    /**
     * @param string $message
     * @param string|null $code
     * @param \Throwable|null $previous
     */
    #[Pure]
    public function __construct($message = '', string $code = null, Throwable $previous = null)
    {
        $this->message = $message;
        $this->code = $code;
    }
}
