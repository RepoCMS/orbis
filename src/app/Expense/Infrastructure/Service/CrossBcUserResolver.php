<?php

declare(strict_types=1);

namespace App\Expense\Infrastructure\Service;

use App\Expense\Application\Service\UserResolverInterface;
use App\Expense\Domain\Exceptions\UserNotFoundException;
use Doctrine\DBAL\Connection;

/**
 * Resuelve un User desde Identity BC vía DBAL directo.
 * Verifica existencia y estado activo (status = 'active').
 */
final readonly class CrossBcUserResolver implements UserResolverInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function findActive(string $userId): void
    {
        $user = $this->connection->fetchAssociative(
            'SELECT id, status FROM users WHERE id = ? AND deleted_at IS NULL',
            [$userId],
        );

        if ($user === false) {
            throw new UserNotFoundException($userId);
        }

        if ($user['status'] !== 'active') {
            throw new UserNotFoundException($userId);
        }
    }
}
