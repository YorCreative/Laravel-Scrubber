<?php

namespace YorCreative\Scrubber\Exceptions;

use Exception;
use Throwable;

class MissingDependencyException extends Exception
{
    public function __construct($message = '', $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function forPackage(string $package, string $provider): self
    {
        return new self(sprintf(
            '%s provider requires %s. Install via: composer require %s',
            $provider,
            $package,
            $package
        ));
    }
}
