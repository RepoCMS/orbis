<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Doctrine\Types;

use App\Identity\Domain\ValueObjects\RefreshTokenIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class RefreshTokenIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'refresh_token_id';
    }

    protected function getValueObjectClass(): string
    {
        return RefreshTokenIdValueObject::class;
    }
}
