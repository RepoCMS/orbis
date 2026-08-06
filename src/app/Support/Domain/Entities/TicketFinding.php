<?php

declare(strict_types=1);

namespace App\Support\Domain\Entities;

use App\Support\Domain\ValueObjects\TicketFindingIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

final class TicketFinding
{
    /** @var TicketSparePart[] */
    private array $spareParts = [];

    /** @var TicketPendingSparePart[] */
    private array $pendingSpareParts = [];

    public function __construct(
        private TicketFindingIdValueObject $id,
        private TicketIdValueObject        $ticketId,
        private ?string                    $catCode,
        private ?string                    $physicalDocNumber,
        private string                     $problemTypeFound,
        private string                     $problemDetail,
        private bool                       $batteryVoltageOk,
        private string                     $solution,
        private string                     $registeredBy,
    ) {
    }

    public static function register(
        TicketFindingIdValueObject $id,
        TicketIdValueObject $ticketId,
        string $problemTypeFound,
        string $problemDetail,
        bool $batteryVoltageOk,
        string $solution,
        string $registeredBy,
        ?string $physicalDocNumber = null,
    ): self {
        if (trim($problemTypeFound) === '') {
            throw new \InvalidArgumentException('El tipo de problema encontrado es obligatorio.');
        }
        if (trim($problemDetail) === '') {
            throw new \InvalidArgumentException('El detalle del problema es obligatorio.');
        }
        if (trim($solution) === '') {
            throw new \InvalidArgumentException('La solución es obligatoria.');
        }
        if (trim($registeredBy) === '') {
            throw new \InvalidArgumentException('El técnico que registra es obligatorio.');
        }

        return new self(
            id: $id,
            ticketId: $ticketId,
            catCode: null,
            physicalDocNumber: $physicalDocNumber,
            problemTypeFound: $problemTypeFound,
            problemDetail: $problemDetail,
            batteryVoltageOk: $batteryVoltageOk,
            solution: $solution,
            registeredBy: $registeredBy,
        );
    }

    public function assignCatCode(string $catCode): void
    {
        if ($this->catCode !== null) {
            throw new \LogicException('El código CAT ya fue asignado.');
        }
        $this->catCode = $catCode;
    }

    public function updatePhysicalDocNumber(?string $docNumber): void
    {
        $this->physicalDocNumber = $docNumber;
    }

    public function id(): TicketFindingIdValueObject
    {
        return $this->id;
    }
    public function ticketId(): TicketIdValueObject
    {
        return $this->ticketId;
    }
    public function catCode(): ?string
    {
        return $this->catCode;
    }
    public function physicalDocNumber(): ?string
    {
        return $this->physicalDocNumber;
    }
    public function problemTypeFound(): string
    {
        return $this->problemTypeFound;
    }
    public function problemDetail(): string
    {
        return $this->problemDetail;
    }
    public function batteryVoltageOk(): bool
    {
        return $this->batteryVoltageOk;
    }
    public function solution(): string
    {
        return $this->solution;
    }
    public function registeredBy(): string
    {
        return $this->registeredBy;
    }

    /** @return TicketSparePart[] */
    public function spareParts(): array
    {
        return $this->spareParts;
    }

    /** @return TicketPendingSparePart[] */
    public function pendingSpareParts(): array
    {
        return $this->pendingSpareParts;
    }
}
