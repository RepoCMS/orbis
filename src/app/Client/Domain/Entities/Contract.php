<?php

declare(strict_types=1);

namespace App\Client\Domain\Entities;

use App\Client\Domain\Enums\ContractStatus;
use App\Client\Domain\Exceptions\ContractStatusException;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ContractIdValueObject;
use App\Shared\Domain\Contracts\TimestampableInterface;
use DateTimeImmutable;

/**
 * Contrato con un cliente.
 * Representa un acuerdo de servicio con fechas, términos y alcance definidos.
 */
final class Contract implements TimestampableInterface
{
    // Campos de auditoría — gestionados por TimestampsSubscriber (Infrastructure)
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt = null;

    private function __construct(
        private ContractIdValueObject $id,
        private ClientIdValueObject $clientId,
        private DateTimeImmutable $startDate,
        private ?DateTimeImmutable $endDate,
        private ?string $terms,
        private ?string $scope,
        private ContractStatus $status,
    ) {
    }

    public static function create(
        ContractIdValueObject $id,
        ClientIdValueObject $clientId,
        DateTimeImmutable $startDate,
        ?DateTimeImmutable $endDate = null,
        ?string $terms = null,
        ?string $scope = null,
        ContractStatus $status = ContractStatus::ACTIVE,
    ): self {
        return new self($id, $clientId, $startDate, $endDate, $terms, $scope, $status);
    }

    public function id(): ContractIdValueObject
    {
        return $this->id;
    }

    public function clientId(): ClientIdValueObject
    {
        return $this->clientId;
    }

    public function startDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function endDate(): ?DateTimeImmutable
    {
        return $this->endDate;
    }

    public function terms(): ?string
    {
        return $this->terms;
    }

    public function scope(): ?string
    {
        return $this->scope;
    }

    public function status(): ContractStatus
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->status === ContractStatus::ACTIVE;
    }

    /**
     * Cancela el contrato.
     * Solo se puede cancelar un contrato activo.
     */
    public function cancel(): void
    {
        if ($this->status !== ContractStatus::ACTIVE) {
            throw new ContractStatusException('Solo se puede cancelar un contrato activo.');
        }

        $this->status = ContractStatus::CANCELLED;
    }

    /**
     * Actualiza los términos del contrato.
     */
    public function updateTerms(?string $terms): void
    {
        $this->terms = $terms;
    }

    /**
     * Actualiza el alcance del contrato.
     */
    public function updateScope(?string $scope): void
    {
        $this->scope = $scope;
    }

    /**
     * Extiende la fecha de finalización del contrato.
     */
    public function extendEndDate(?DateTimeImmutable $endDate): void
    {
        $this->endDate = $endDate;
    }
}
