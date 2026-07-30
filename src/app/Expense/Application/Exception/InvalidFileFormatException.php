<?php

declare(strict_types=1);

namespace App\Expense\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class InvalidFileFormatException extends ApplicationException
{
    public function __construct(string $format)
    {
        parent::__construct(
            sprintf('Formato de archivo no soportado: %s. Use jpg, png o pdf.', $format),
            422
        );
    }
}
