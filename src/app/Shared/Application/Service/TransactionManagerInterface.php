<?php

declare(strict_types=1);

namespace App\Shared\Application\Service;

/**
 * Application-layer abstraction for transaction management.
 *
 * Implementations (e.g. DoctrineTransactionManager) live in Infrastructure.
 * Application handlers depend ONLY on this interface — not on ORM/framework.
 */
interface TransactionManagerInterface
{
    public function begin(): void;

    public function commit(): void;

    public function rollback(): void;
}
