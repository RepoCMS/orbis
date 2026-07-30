<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Import;

use App\Inventory\Domain\Enums\ProductTrackingType;
use App\Shared\Application\Import\RowValidatorInterface;

final readonly class ProductRowValidator implements RowValidatorInterface
{
    /** @param array<string, string> $row */
    public function validate(array $row, int $rowNumber): array
    {
        $errors = [];

        $code = trim($row['code'] ?? '');
        if ($code === '') {
            $errors[] = sprintf('Fila %d: Código es obligatorio', $rowNumber);
        } elseif (strlen($code) > 50) {
            $errors[] = sprintf('Fila %d: Código no puede exceder 50 caracteres', $rowNumber);
        }

        $name = trim($row['name'] ?? '');
        if ($name === '') {
            $errors[] = sprintf('Fila %d: Nombre es obligatorio', $rowNumber);
        } elseif (strlen($name) > 150) {
            $errors[] = sprintf('Fila %d: Nombre no puede exceder 150 caracteres', $rowNumber);
        }

        $trackingType = trim($row['tracking_type'] ?? '');
        if ($trackingType === '') {
            $errors[] = sprintf('Fila %d: Tipo de tracking es obligatorio', $rowNumber);
        } elseif (!in_array($trackingType, [ProductTrackingType::SERIALIZED->value, ProductTrackingType::NON_SERIALIZED->value], true)) {
            $errors[] = sprintf('Fila %d: Tipo de tracking inválido ("%s"). Valores: serialized, non_serialized', $rowNumber, $trackingType);
        }

        return $errors;
    }
}
