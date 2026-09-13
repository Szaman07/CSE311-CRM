<?php

namespace App\Domain;

use RuntimeException;

final class DomainConflict extends RuntimeException
{
    public function __construct(public readonly string $errorCode, string $message, public readonly array $context = [])
    {
        parent::__construct($message);
    }
}
