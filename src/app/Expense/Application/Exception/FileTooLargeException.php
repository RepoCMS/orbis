<?php

declare(strict_types=1);

namespace App\Expense\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class FileTooLargeException extends ApplicationException
{
    public function __construct(int $maxSize = 10)
    {
        parent::__construct(
            sprintf('El archivo excede el tamaño máximo permitido de %d MB', $maxSize),
            413
        );
    }
}
