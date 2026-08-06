<?php

declare(strict_types=1);

namespace App\Identity\Domain\Repositories;

use App\Identity\Domain\Entities\UserMfaBackupCode;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UserMfaBackupCodeIdValueObject;

interface UserMfaBackupCodeRepositoryInterface
{
    /**
     * @return UserMfaBackupCode[]
     */
    public function findUnusedByUser(UserIdValueObject $userId): array;

    public function findById(UserMfaBackupCodeIdValueObject $id): ?UserMfaBackupCode;

    public function findByCode(UserIdValueObject $userId, string $code): ?UserMfaBackupCode;

    public function save(UserMfaBackupCode $backupCode): void;

    public function markAsUsed(UserMfaBackupCodeIdValueObject $id): void;

    public function countUnusedByUser(UserIdValueObject $userId): int;

    public function deleteAllByUser(UserIdValueObject $userId): void;
}
