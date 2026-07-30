<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Doctrine\Types;

use App\Identity\Domain\Enums\AuthEvent;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class AuthEventType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'auth_event';
    }

    protected function getEnumClass(): string
    {
        return AuthEvent::class;
    }
}
