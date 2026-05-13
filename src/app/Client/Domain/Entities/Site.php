<?php

declare(strict_types=1);

namespace App\Client\Domain\Entities;

use App\Client\Domain\Enums\SiteStatus;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\SiteIdValueObject;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;
use DateTimeImmutable;

/**
 * Sucursal de un cliente donde se ubican cajeros automáticos.
 *
 * cityId: ULID string de la ciudad (Location BC — pendiente de implementar tipo Doctrine).
 * serviceZoneId: referencia a ServiceZone (Organization BC) — permite detectar atenciones fuera de zona.
 */
final class Site
{
    private ?DateTimeImmutable $deletedAt = null;

    private function __construct(
        private SiteIdValueObject $id,
        private ClientIdValueObject $clientId,
        private string $cityId,
        private TerminalIdValueObject $terminalId,
        private string $name,
        private SiteStatus $status = SiteStatus::ACTIVE,
        private ?ServiceZoneIdValueObject $serviceZoneId = null,
    ) {
    }

    public static function add(
        SiteIdValueObject $id,
        ClientIdValueObject $clientId,
        string $cityId,
        TerminalIdValueObject $terminalId,
        string $name,
        SiteStatus $status = SiteStatus::ACTIVE,
        ?ServiceZoneIdValueObject $serviceZoneId = null,
    ): self {
        return new self($id, $clientId, $cityId, $terminalId, $name, $status, $serviceZoneId);
    }

    public function id(): SiteIdValueObject
    {
        return $this->id;
    }

    public function clientId(): ClientIdValueObject
    {
        return $this->clientId;
    }

    public function cityId(): string
    {
        return $this->cityId;
    }

    public function terminalId(): TerminalIdValueObject
    {
        return $this->terminalId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function status(): SiteStatus
    {
        return $this->status;
    }

    public function serviceZoneId(): ?ServiceZoneIdValueObject
    {
        return $this->serviceZoneId;
    }

    public function changeName(string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('El nombre de la sucursal no puede estar vacío.');
        }
        $this->name = $name;
    }

    public function assignToZone(ServiceZoneIdValueObject $zoneId): void
    {
        $this->serviceZoneId = $zoneId;
    }

    public function removeFromZone(): void
    {
        $this->serviceZoneId = null;
    }

    public function deactivate(): void
    {
        $this->status = SiteStatus::INACTIVE;
    }

    public function reactivate(): void
    {
        $this->status = SiteStatus::ACTIVE;
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function softDelete(): void
    {
        $this->deletedAt = new DateTimeImmutable();
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }
}
