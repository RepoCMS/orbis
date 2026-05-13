<?php

declare(strict_types=1);

namespace App\Client\Domain\Entities;

use App\Client\Domain\Enums\ClientStatus;
use App\Client\Domain\Exceptions\ClientStatusException;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ClientNameValueObject;
use App\Client\Domain\ValueObjects\ClientRucValueObject;
use App\Shared\Domain\Contracts\TimestampableInterface;
use DateTimeImmutable;

/**
 * Cliente (empresa/entidad) que contrata servicios.
 * Aggregate root con su propio ciclo de vida ACTIVE/INACTIVE.
 */
final class Client implements TimestampableInterface
{
    // Campos de auditoría — gestionados por TimestampsSubscriber (Infrastructure)
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt = null;
    private ?DateTimeImmutable $deletedAt = null;

    private ?string $address;

    private function __construct(
        private ClientIdValueObject $id,
        private ClientNameValueObject $name,
        private ClientRucValueObject $ruc,
        ?string $address = null,
        private ClientStatus $status = ClientStatus::ACTIVE,
    ) {
        $this->address = $address !== null ? trim($address) : null;
    }

    public function id(): ClientIdValueObject
    {
        return $this->id;
    }

    public function name(): ClientNameValueObject
    {
        return $this->name;
    }

    public function ruc(): ClientRucValueObject
    {
        return $this->ruc;
    }

    public function address(): ?string
    {
        return $this->address;
    }

    public function status(): ClientStatus
    {
        return $this->status;
    }

    /**
     * Comportamiento de un cliente:
     * - Registrar un nuevo cliente (con estado ACTIVE por defecto).
     * - Cambiar el nombre o dirección de un cliente.
     * - Activar o desactivar un cliente (cambiar su estado entre ACTIVE e INACTIVE).
     * - Validar que no se puedan activar un cliente ya activo o desactivar un cliente ya inactivo, lanzando excepciones en esos casos.
     * - Validar que el RUC sea único entre los clientes, lanzando una excepción si se intenta registrar un cliente con un RUC que ya existe. (Esta validación se haría a nivel de repositorio o servicio, no en la entidad directamente).
     */

    public function isActive(): bool
    {
        return $this->status === ClientStatus::ACTIVE;
    }

    public static function register(
        ClientIdValueObject $id,
        ClientNameValueObject $name,
        ClientRucValueObject $ruc,
        ?string $address = null,
    ): self {
        return new self($id, $name, $ruc, $address);
    }

    public static function createForTest(
        ClientIdValueObject $id,
        ClientNameValueObject $name,
        ClientRucValueObject $ruc,
        ?string $address = null,
        ClientStatus $status = ClientStatus::ACTIVE
    ): self {
        return new self($id, $name, $ruc, $address, $status);
    }

    public function changeName(ClientNameValueObject $newName): void
    {
        $this->name = $newName;
    }

    public function changeAddress(?string $newAddress): void
    {
        $this->address = $newAddress !== null ? trim($newAddress) : null;
    }

    public function activate(): void
    {
        if ($this->isActive()) {
            throw new ClientStatusException('El cliente ya se encuentra activo.');
        }

        $this->status = ClientStatus::ACTIVE;
    }

    public function deactivate(): void
    {
        if (!$this->isActive()) {
            throw new ClientStatusException('El cliente ya se encuentra inactivo.');
        }

        $this->status = ClientStatus::INACTIVE;
    }

    public function deletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function softDelete(): void
    {
        $this->deletedAt = new \DateTimeImmutable();
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }
}
