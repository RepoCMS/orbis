<?php

declare(strict_types=1);

namespace App\Client\Domain\Entities;

use App\Client\Domain\Enums\TerminalFunctionality;
use App\Client\Domain\Enums\TerminalStatus;
use App\Client\Domain\Enums\TerminalType;
use App\Client\Domain\Enums\TerminalVendor;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\SerialNumberValueObject;
use DateTimeImmutable;

/**
 * Terminal de un cliente.
 * Representa un terminal de cajero automático perteneciente a un cliente.
 *
 */
final class Terminal
{
    private ?DateTimeImmutable $deletedAt = null;

    private function __construct(
        private TerminalIdValueObject $id,
        private ClientIdValueObject $clientId,
        private SerialNumberValueObject $serial,
        private TerminalVendor $vendor,
        private TerminalFunctionality $funcionality,
        private TerminalType $type,
        private TerminalStatus $status,
    ) {
    }

    public static function add(
        TerminalIdValueObject $id,
        ClientIdValueObject $clientId,
        SerialNumberValueObject $serial,
        TerminalVendor $vendor,
        TerminalFunctionality $funcionality,
        TerminalType $type,
        TerminalStatus $status = TerminalStatus::ACTIVE,
    ): self {
        return new self($id, $clientId, $serial, $vendor, $funcionality, $type, $status);
    }

    public function id(): TerminalIdValueObject
    {
        return $this->id;
    }

    public function clientId(): ClientIdValueObject
    {
        return $this->clientId;
    }

    public function vendor(): TerminalVendor
    {
        return $this->vendor;
    }

    public function serial(): SerialNumberValueObject
    {
        return $this->serial;
    }

    public function funcionality(): TerminalFunctionality
    {
        return $this->funcionality;
    }

    public function type(): TerminalType
    {
        return $this->type;
    }

    public function status(): TerminalStatus
    {
        return $this->status;
    }

    public function activate(): void
    {
        $this->status = TerminalStatus::ACTIVE;
    }

    public function deactivate(): void
    {
        $this->status = TerminalStatus::INACTIVE;
    }

    public function isActive(): bool
    {
        return $this->status === TerminalStatus::ACTIVE;
    }

    public function changeVendor(TerminalVendor $vendor): void
    {
        $this->vendor = $vendor;
    }

    public function changeSerial(SerialNumberValueObject $serial): void
    {
        $this->serial = $serial;
    }

    public function changeFuncionality(TerminalFunctionality $funcionality): void
    {
        $this->funcionality = $funcionality;
    }

    public function changeType(TerminalType $type): void
    {
        $this->type = $type;
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