<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Import;

use App\Shared\Application\Import\RowValidatorInterface;

final readonly class PurchaseOrderRowValidator implements RowValidatorInterface
{
    /** @param array<string, string> $row */
    public function validate(array $row, int $rowNumber): array
    {
        $errors = [];

        $reference = trim($row['reference'] ?? '');
        if ($reference === '') {
            $errors[] = sprintf('Fila %d: Referencia es obligatoria', $rowNumber);
        } elseif (strlen($reference) > 100) {
            $errors[] = sprintf('Fila %d: Referencia no puede exceder 100 caracteres', $rowNumber);
        }

        $items = trim($row['items'] ?? '');
        if ($items === '') {
            $errors[] = sprintf('Fila %d: Items es obligatorio (formato: codigo:cantidad|codigo2:cantidad)', $rowNumber);
        } else {
            $parts = explode('|', $items);
            foreach ($parts as $i => $item) {
                $itemParts = explode(':', $item);
                if (count($itemParts) !== 2 || trim($itemParts[0]) === '' || !ctype_digit(trim($itemParts[1])) || (int) trim($itemParts[1]) <= 0) {
                    $errors[] = sprintf('Fila %d: Item #%d inválido. Use formato "codigo:cantidad"', $rowNumber, $i + 1);
                }
            }
        }

        return $errors;
    }
}
