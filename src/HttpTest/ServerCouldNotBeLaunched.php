<?php

namespace HttpTest;

use RuntimeException;

final class ServerCouldNotBeLaunched extends RuntimeException
{
    public static function forMaxAttempts($retries)
    {
        return new self(sprintf('Could not launch server after %d attempts', $retries));
    }

    public static function forFailedForking()
    {
        return new self('Could not fork the process');
    }
}
