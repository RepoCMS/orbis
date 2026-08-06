<?php

declare(strict_types=1);

namespace App\Notification\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class CannotMarkReadNonInAppException extends DomainException
{
}
