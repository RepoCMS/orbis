<?php

declare(strict_types=1);

namespace App\Client\Domain\Entities;

use App\Client\Domain\Enums\TerminalAssignmentStatus;
use App\Client\Domain\ValueObjects\TerminalAssignmentIdValueObject;
use App\Client\Domain\ValueObjects\SiteIdValueObject;
use App\Client\Domain\ValueObjects\TerminalIdAssignedByClientValueObject;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Client\Domain\ValueObjects\TerminalNameValueObject;
use DateTimeImmutable;

/**
 * TerminalAssignment
 * Representación lógica de un cajero automático del catálogo perteneciente a un cliente.
 * Identidad en un período de tiempo.
 */
final class TerminalAssignment
{
    private ?DateTimeImmutable $deletedAt = null;

    private function __construct(
        private TerminalAssignmentIdValueObject $id,
        private TerminalIdValueObject $terminalId,
        private TerminalNameValueObject $name,
        private TerminalIdAssignedByClientValueObject $idAssingedByClient,
        private SiteIdValueObject $siteId,
        private TerminalAssignmentStatus $status = TerminalAssignmentStatus::OPERATIVE,
    ) {
    }

    public static function add(
        TerminalAssignmentIdValueObject $id,
        TerminalIdValueObject $terminalId,
        TerminalNameValueObject $name,
        TerminalIdAssignedByClientValueObject $idAssingedByClient,
        SiteIdValueObject $siteId,
        TerminalAssignmentStatus $status = TerminalAssignmentStatus::OPERATIVE,
    ): self {
        return new self($id, $terminalId, $name, $idAssingedByClient, $siteId, $status);
    }

    public function id(): TerminalAssignmentIdValueObject
    {
        return $this->id;
    }

    public function terminalId(): TerminalIdValueObject
    {
        return $this->terminalId;
    }

    public function siteId(): SiteIdValueObject
    {
        return $this->siteId;
    }

    public function status(): TerminalAssignmentStatus
    {
        return $this->status;
    }

    public function name(): TerminalNameValueObject
    {
        return $this->name;
    }

    public function idAssingedByClient(): TerminalIdAssignedByClientValueObject
    {
        return $this->idAssingedByClient;
    }

    public function activate(): void
    {
        $this->status = TerminalAssignmentStatus::OPERATIVE;
    }

    public function deactivate(): void
    {
        $this->status = TerminalAssignmentStatus::INOPERATIVE;
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

    public function changeName(string $name): void
    {
        $this->name = new TerminalNameValueObject($name);
    }

    public function changeIdAssingedByClient(string $idAssingedByClient): void
    {
        $this->idAssingedByClient = new TerminalIdAssignedByClientValueObject($idAssingedByClient);
    }

    public function changeSiteId(SiteIdValueObject $siteId): void
    {
        $this->siteId = $siteId;
    }
}