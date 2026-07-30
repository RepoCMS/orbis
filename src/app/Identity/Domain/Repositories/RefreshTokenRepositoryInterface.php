<?php

declare(strict_types=1);

namespace App\Identity\Domain\Repositories;

use App\Identity\Domain\Entities\RefreshToken;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use DateTimeImmutable;

interface RefreshTokenRepositoryInterface
{
    public function save(RefreshToken $token): void;

    public function findByHash(string $tokenHash): ?RefreshToken;

    /**
     * @return RefreshToken[]
     */
    public function findByUserId(UserIdValueObject $userId): array;

    public function deleteExpired(DateTimeImmutable $now): int;
}
