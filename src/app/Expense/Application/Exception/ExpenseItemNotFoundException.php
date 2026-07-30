<?php

declare(strict_types=1);

namespace App\Expense\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class ExpenseItemNotFoundException extends ApplicationException
{
    public function __construct(string $itemId)
    {
        parent::__construct(
            sprintf('Item de gasto no encontrado: %s', $itemId),
            404
        );
    }
}
