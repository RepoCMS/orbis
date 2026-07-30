<?php

declare(strict_types=1);

namespace App\Expense\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class CashAdvanceNotFoundException extends ApplicationException
{
    public function __construct(string $cashAdvanceId)
    {
        parent::__construct(
            sprintf('Adelanto de efectivo no encontrado: %s', $cashAdvanceId),
        );
    }
}
