<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObjects;

use App\Shared\Exceptions\DomainException;

final class ProcessedCommandInvalidIdException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Invalid ProcessedCommand ID format.');
    }
}
