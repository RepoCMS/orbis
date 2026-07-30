<?php

declare(strict_types=1);

namespace App\Client\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class ModelPartNumberException extends DomainException
{
    public function __construct(string $message = '')
    {
        parent::__construct($message);
    }
}
