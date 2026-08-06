<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\Response;

use App\Identity\Domain\Entities\User;
use DateTimeImmutable;

final readonly class UserResponse
{
    public function __construct(
        public string $id,
        public string $username,
        public string $roleId,
        public string $status,
        public int $failedLoginAttempts,
        public ?DateTimeImmutable $lockedUntil,
        public bool $isLocked,
        public ?DateTimeImmutable $deletedAt,
        public bool $isDeleted,
    ) {
    }

    public static function fromEntity(User $user): self
    {
        return new self(
            id: (string) $user->id(),
            username: (string) $user->username(),
            roleId: (string) $user->roleId(),
            status: $user->status()->value,
            failedLoginAttempts: $user->failedLoginAttempts(),
            lockedUntil: $user->lockedUntil(),
            isLocked: $user->isLocked(),
            deletedAt: $user->deletedAt(),
            isDeleted: $user->isDeleted(),
        );
    }
}
