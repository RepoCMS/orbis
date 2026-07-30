<?php

declare(strict_types=1);

namespace App\Expense\Domain\Entities;

use App\Expense\Domain\Enums\CashAdvanceStatus;
use App\Expense\Domain\Enums\SettlementDirection;
use App\Expense\Domain\Events\CashAdvanceCreated;
use App\Expense\Domain\Events\CashAdvancePendingReview;
use App\Expense\Domain\Events\CashAdvanceSettled;
use App\Expense\Domain\Events\CashAdvanceUpdated;
use App\Expense\Domain\Exceptions\CashAdvanceNotOutstandingException;
use App\Expense\Domain\Exceptions\InvalidAmountException;
use App\Expense\Domain\ValueObjects\CashAdvanceIdValueObject;
use App\Shared\Domain\Contracts\TimestampableInterface;
use App\Shared\Domain\Event\DomainEvent;
use DateTimeImmutable;

final class CashAdvance implements TimestampableInterface
{
    /** @var DomainEvent[] */
    private array $domainEvents = [];

    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt = null;
    private ?string $voucherPath = null;
    private ?string $settlementConfirmedBy = null;
    private ?DateTimeImmutable $settlementConfirmedAt = null;
    private ?SettlementDirection $settlementDirection = null;
    private ?DateTimeImmutable $deletedAt = null;

    private function __construct(
        private CashAdvanceIdValueObject $id,
        private string $advanceNumber,
        private string $employeeId,
        private string $givenBy,
        private float $amount,
        private DateTimeImmutable $givenAt,
        private CashAdvanceStatus $status = CashAdvanceStatus::OUTSTANDING,
    ) {
        $this->createdAt = new DateTimeImmutable();
    }

    public static function create(
        CashAdvanceIdValueObject $id,
        string $advanceNumber,
        string $employeeId,
        string $givenBy,
        float $amount,
        DateTimeImmutable $givenAt,
    ): self {
        if (trim($employeeId) === '') {
            throw new \InvalidArgumentException('El empleado es obligatorio');
        }
        if (trim($givenBy) === '') {
            throw new \InvalidArgumentException('El coordinador es obligatorio');
        }
        if ($amount <= 0) {
            throw InvalidAmountException::mustBePositive();
        }

        $cashAdvance = new self(
            id: $id,
            advanceNumber: $advanceNumber,
            employeeId: $employeeId,
            givenBy: $givenBy,
            amount: $amount,
            givenAt: $givenAt,
            status: CashAdvanceStatus::OUTSTANDING,
        );

        $cashAdvance->recordEvent(new CashAdvanceCreated(
            cashAdvanceId: (string) $id,
            employeeId: $employeeId,
            amount: $amount,
            advanceNumber: $advanceNumber,
        ));

        return $cashAdvance;
    }

    /** @return DomainEvent[] */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }

    private function recordEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    // --- Accessors ---

    public function id(): CashAdvanceIdValueObject
    {
        return $this->id;
    }

    public function advanceNumber(): string
    {
        return $this->advanceNumber;
    }

    public function employeeId(): string
    {
        return $this->employeeId;
    }

    public function givenBy(): string
    {
        return $this->givenBy;
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public function givenAt(): DateTimeImmutable
    {
        return $this->givenAt;
    }

    public function voucherPath(): ?string
    {
        return $this->voucherPath;
    }

    public function status(): CashAdvanceStatus
    {
        return $this->status;
    }

    public function settlementConfirmedBy(): ?string
    {
        return $this->settlementConfirmedBy;
    }

    public function settlementConfirmedAt(): ?DateTimeImmutable
    {
        return $this->settlementConfirmedAt;
    }

    public function settlementDirection(): ?SettlementDirection
    {
        return $this->settlementDirection;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    // --- Behaviour ---

    public function settle(string $confirmedBy, SettlementDirection $direction): void
    {
        if (!$this->status->canSettle()) {
            throw CashAdvanceNotOutstandingException::withStatus($this->status);
        }

        $this->status = CashAdvanceStatus::SETTLED;
        $this->settlementConfirmedBy = $confirmedBy;
        $this->settlementConfirmedAt = new DateTimeImmutable();
        $this->settlementDirection = $direction;

        $this->recordEvent(new CashAdvanceSettled(
            cashAdvanceId: (string) $this->id,
            employeeId: $this->employeeId,
            amount: $this->amount,
            direction: $direction->value,
            confirmedBy: $confirmedBy,
        ));

        $this->markAsUpdated();
    }

    public function markPendingReview(): void
    {
        $this->status = CashAdvanceStatus::PENDING_REVIEW;

        $this->recordEvent(new CashAdvancePendingReview(
            cashAdvanceId: (string) $this->id,
            employeeId: $this->employeeId,
            givenBy: $this->givenBy,
            amount: $this->amount,
        ));

        $this->markAsUpdated();
    }

    /**
     * Verifica si el adelanto está vencido según la cantidad de días
     * desde que fue entregado.
     */
    public function isOverdue(int $daysLimit): bool
    {
        $now = new DateTimeImmutable();
        $diff = $now->diff($this->givenAt);

        return $diff->days > $daysLimit && $this->status->isOutstanding();
    }

    public function uploadVoucher(string $path): void
    {
        if (trim($path) === '') {
            throw new \InvalidArgumentException('La ruta del comprobante no puede estar vacía');
        }

        $this->voucherPath = $path;
        $this->markAsUpdated();
    }

    public function updateAmount(float $newAmount): void
    {
        if (!$this->status->canSettle()) {
            throw CashAdvanceNotOutstandingException::withStatus($this->status);
        }

        if ($newAmount <= 0) {
            throw InvalidAmountException::mustBePositive();
        }

        $previousAmount = $this->amount;
        $this->amount = $newAmount;

        $this->recordEvent(new CashAdvanceUpdated(
            cashAdvanceId: (string) $this->id,
            previousAmount: $previousAmount,
            newAmount: $newAmount,
        ));

        $this->markAsUpdated();
    }

    public function softDelete(): void
    {
        if ($this->status === CashAdvanceStatus::SETTLED) {
            throw new \InvalidArgumentException('No se puede eliminar un adelanto ya liquidado');
        }

        $this->deletedAt = new DateTimeImmutable();
        $this->markAsUpdated();
    }

    public function restore(): void
    {
        $this->deletedAt = null;
        $this->markAsUpdated();
    }

    // --- Internal ---

    private function markAsUpdated(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
