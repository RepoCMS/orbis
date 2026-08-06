<?php

declare(strict_types=1);

namespace App\Support\Domain\Entities;

use App\Support\Domain\AtmModel\FindingType;
use App\Support\Domain\AtmModel\ModulePathValueObject;
use App\Support\Domain\ValueObjects\FindingEvidenceIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketModuleFindingIdValueObject;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Entidad que representa el hallazgo de un técnico sobre un componente específico
 * del árbol de módulos de un ATM durante una asistencia.
 *
 * Cada TicketModuleFinding se asocia a un Ticket (ManyToOne) y puede tener
 * múltiples evidencias (OneToMany → FindingEvidence).
 */
final class TicketModuleFinding
{
    private Collection $evidences;

    /** Hydrated by Doctrine via ManyToOne association */
    private ?Ticket $ticket = null;

    private function __construct(
        private TicketModuleFindingIdValueObject $id,
        private TicketIdValueObject $ticketId,
        private ModulePathValueObject $modulePath,
        private string $componentName,
        private FindingType $findingType,
        private ?string $notes,
        private string $registeredBy,
        private DateTimeImmutable $registeredAt,
    ) {
        $this->evidences = new ArrayCollection();
    }

    public static function record(
        TicketModuleFindingIdValueObject $id,
        TicketIdValueObject $ticketId,
        ModulePathValueObject $modulePath,
        string $componentName,
        FindingType $findingType,
        string $registeredBy,
        ?string $notes = null,
    ): self {
        if (trim($componentName) === '') {
            throw new \InvalidArgumentException('El nombre del componente es obligatorio.');
        }
        if (trim($registeredBy) === '') {
            throw new \InvalidArgumentException('El usuario que registra es obligatorio.');
        }

        return new self(
            id: $id,
            ticketId: $ticketId,
            modulePath: $modulePath,
            componentName: $componentName,
            findingType: $findingType,
            notes: $notes,
            registeredBy: $registeredBy,
            registeredAt: new DateTimeImmutable(),
        );
    }

    public function addEvidence(FindingEvidence $evidence): void
    {
        $evidence->assignTo($this);
        $this->evidences->add($evidence);
    }

    public function changeFindingType(FindingType $findingType, ?string $notes = null): void
    {
        $this->findingType = $findingType;
        $this->notes = $notes;
    }

    public function removeEvidence(FindingEvidenceIdValueObject $id): void
    {
        foreach ($this->evidences as $evidence) {
            if ($evidence->id()->__toString() === (string) $id) {
                $this->evidences->removeElement($evidence);

                return;
            }
        }
    }

    public function id(): TicketModuleFindingIdValueObject
    {
        return $this->id;
    }

    public function ticketId(): TicketIdValueObject
    {
        return $this->ticketId;
    }

    public function modulePath(): ModulePathValueObject
    {
        return $this->modulePath;
    }

    public function componentName(): string
    {
        return $this->componentName;
    }

    public function findingType(): FindingType
    {
        return $this->findingType;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function registeredBy(): string
    {
        return $this->registeredBy;
    }

    public function registeredAt(): DateTimeImmutable
    {
        return $this->registeredAt;
    }

    /** @return FindingEvidence[] */
    public function evidences(): array
    {
        return $this->evidences->toArray();
    }

    public function ticket(): ?Ticket
    {
        return $this->ticket;
    }
}
