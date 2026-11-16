<?php

declare(strict_types=1);

namespace Seedess;

class SeedessException extends \RuntimeException
{
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
        public readonly mixed $body = null,
    ) {
        parent::__construct($message);
    }
}
