<?php

declare(strict_types=1);

namespace App\Organization\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

/**
 * Lanzada cuando se intenta terminar un empleado que tiene items pendientes
 * que deben resolverse primero (tickets abiertos, reparaciones pendientes, etc.).
 */
final class TerminationBlockedException extends ApplicationException
{
    /**
     * @param array<string, array<array{type: string, description: string}>> $pendingItems Agrupados por BC
     */
    public function __construct(
        string $message,
        public readonly array $pendingItems = [],
    ) {
        parent::__construct($message);
    }

    public static function withPendingItems(array $pendingItems): self
    {
        $summary = [];
        foreach ($pendingItems as $bc => $items) {
            $summary[] = sprintf('%s (%d)', $bc, count($items));
        }

        return new self(
            sprintf(
                'No se puede desactivar el empleado porque tiene los siguientes items pendientes: %s.',
                implode(', ', $summary),
            ),
            $pendingItems,
        );
    }
}
