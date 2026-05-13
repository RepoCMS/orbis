<?php

declare(strict_types=1);

namespace App\Organization\Domain\Entities;

use App\Organization\Domain\Enums\ServiceZoneStatus;
use App\Organization\Domain\Exceptions\ServiceZoneException;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;
use App\Shared\Domain\Contracts\TimestampableInterface;
use DateTimeImmutable;

/**
 * Zona operativa de la empresa que agrupa sucursales (Sites) de clientes.
 *
 * En el sistema legado: customerLocationService (CLS).
 *
 * Reglas clave:
 *   - Los técnicos (Employee con profile=TECHNICIAN) se asignan a una zona via Employee.serviceZoneId.
 *   - Los Site de clientes referencian su zona via Site.serviceZoneId.
 *   - Una ciudad no puede pertenecer a dos zonas activas simultáneamente (se valida en Application Layer).
 *   - No se puede eliminar una zona con empleados o sites activos asignados (validado en Application Layer).
 *   - cityIds son ULID strings de Location BC — referencia sin acoplamiento de dominio.
 */
final class ServiceZone implements TimestampableInterface
{
    private const MIN_NAME_LENGTH = 3;
    private const MAX_NAME_LENGTH = 100;

    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt = null;
    private ?DateTimeImmutable $deletedAt = null;

    /** @var string[] ULID strings de ciudades (Location BC) */
    private array $cityIds;

    private function __construct(
        private ServiceZoneIdValueObject $id,
        private string $name,
        private ServiceZoneStatus $status,
        array $cityIds = [],
    ) {
        $this->assertName($name);
        $this->cityIds = array_values(array_unique($cityIds));
    }

    public static function create(
        ServiceZoneIdValueObject $id,
        string $name,
    ): self {
        return new self($id, $name, ServiceZoneStatus::ACTIVE);
    }

    // ── Comportamientos ─────────────────────────────────────────────────────

    public function changeName(string $name): void
    {
        $this->assertName($name);
        $this->name = $name;
    }

    /**
     * Agrega una ciudad a la zona.
     * La validación de solapamiento entre zonas se hace en el Application Layer.
     */
    public function addCity(string $cityId): void
    {
        $cityId = strtoupper(trim($cityId));

        if (in_array($cityId, $this->cityIds, true)) {
            return; // idempotente
        }

        $this->cityIds[] = $cityId;
    }

    public function removeCity(string $cityId): void
    {
        $cityId = strtoupper(trim($cityId));
        $this->cityIds = array_values(
            array_filter($this->cityIds, static fn(string $c) => $c !== $cityId)
        );
    }

    public function deactivate(): void
    {
        if ($this->status === ServiceZoneStatus::INACTIVE) {
            throw new ServiceZoneException('La zona ya se encuentra inactiva.');
        }

        $this->status = ServiceZoneStatus::INACTIVE;
    }

    public function reactivate(): void
    {
        if ($this->status === ServiceZoneStatus::ACTIVE) {
            throw new ServiceZoneException('La zona ya se encuentra activa.');
        }

        $this->status = ServiceZoneStatus::ACTIVE;
    }

    public function softDelete(): void
    {
        $this->deletedAt = new DateTimeImmutable();
    }

    // ── Getters ─────────────────────────────────────────────────────────────

    public function id(): ServiceZoneIdValueObject
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function status(): ServiceZoneStatus
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->status === ServiceZoneStatus::ACTIVE;
    }

    /** @return string[] */
    public function cityIds(): array
    {
        return $this->cityIds;
    }

    public function hasCity(string $cityId): bool
    {
        return in_array(strtoupper(trim($cityId)), $this->cityIds, true);
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    // ── Invariantes ──────────────────────────────────────────────────────────

    private function assertName(string $name): void
    {
        $trimmed = trim($name);
        $length  = mb_strlen($trimmed);

        if ($length < self::MIN_NAME_LENGTH) {
            throw new ServiceZoneException('El nombre de la zona debe tener al menos ' . self::MIN_NAME_LENGTH . ' caracteres.');
        }

        if ($length > self::MAX_NAME_LENGTH) {
            throw new ServiceZoneException('El nombre de la zona excede el máximo de ' . self::MAX_NAME_LENGTH . ' caracteres.');
        }

        $this->name = $trimmed;
    }
}
