<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Doctrine\Types;

use App\Identity\Domain\ValueObjects\UserMfaBackupCodeIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class UserMfaBackupCodeIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'user_mfa_backup_code_id';
    }

    protected function getValueObjectClass(): string
    {
        return UserMfaBackupCodeIdValueObject::class;
    }
}
