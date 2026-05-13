<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Doctrine\Types;

use App\Identity\Domain\Enums\RoleStatus;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class RoleStatusType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'role_status';
    }

    protected function getEnumClass(): string
    {
        return RoleStatus::class;
    }
}
