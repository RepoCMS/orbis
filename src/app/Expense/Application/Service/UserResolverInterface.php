<?php

declare(strict_types=1);

namespace App\Expense\Application\Service;

use App\Expense\Domain\Exceptions\UserNotFoundException;

interface UserResolverInterface
{
    /**
     * Validates that a user exists in Identity BC and is active.
     *
     * @throws UserNotFoundException
     */
    public function findActive(string $userId): void;
}
