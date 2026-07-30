<?php

declare(strict_types=1);

namespace App\Support\Domain\Entities;

use App\Support\Domain\ValueObjects\FindingComponentIdValueObject;
use App\Support\Domain\ValueObjects\TicketFindingIdValueObject;

/**
 * Entidad que representa un componente de ATM asociado a un diagnóstico (TicketFinding).
 */
final class FindingComponent
{
    private function __construct(
        private FindingComponentIdValueObject $id,
        private TicketFindingIdValueObject $findingId,
        private string $componentId,
    ) {}

    public static function create(
        FindingComponentIdValueObject $id,
        TicketFindingIdValueObject $findingId,
        string $componentId,
    ): self {
        if (trim($componentId) === '') {
            throw new \InvalidArgumentException('El ID del componente es obligatorio.');
        }

        return new self(
            id: $id,
            findingId: $findingId,
            componentId: $componentId,
        );
    }

    public function id(): FindingComponentIdValueObject
    {
        return $this->id;
    }

    public function findingId(): TicketFindingIdValueObject
    {
        return $this->findingId;
    }

    public function componentId(): string
    {
        return $this->componentId;
    }
}
