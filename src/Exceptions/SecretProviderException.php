<?php

namespace YorCreative\Scrubber\Exceptions;

use Exception;
use Throwable;

class SecretProviderException extends Exception
{
    public function __construct($message = '', $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
