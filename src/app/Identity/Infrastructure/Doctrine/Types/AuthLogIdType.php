<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Doctrine\Types;

use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;
use App\Identity\Domain\ValueObjects\AuthLogIdValueObject;

final class AuthLogIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'auth_log_id';
    }

    protected function getValueObjectClass(): string
    {
        return AuthLogIdValueObject::class;
    }
}
