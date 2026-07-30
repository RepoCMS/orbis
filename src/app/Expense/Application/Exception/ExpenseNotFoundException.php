<?php

declare(strict_types=1);

namespace App\Expense\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class ExpenseNotFoundException extends ApplicationException
{
    public function __construct(string $expenseId)
    {
        parent::__construct(
            sprintf('Liquidación no encontrada: %s', $expenseId),
            404
        );
    }
}
