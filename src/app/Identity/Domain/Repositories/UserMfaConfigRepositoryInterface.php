<?php

declare(strict_types=1);

namespace App\Identity\Domain\Repositories;

use App\Identity\Domain\Entities\UserMfaConfig;
use App\Identity\Domain\ValueObjects\UserIdValueObject;

interface UserMfaConfigRepositoryInterface
{
    public function findByUserId(UserIdValueObject $userId): ?UserMfaConfig;

    public function save(UserMfaConfig $config): void;

    public function deleteByUserId(UserIdValueObject $userId): void;
}
