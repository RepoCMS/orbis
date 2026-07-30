<?php

declare(strict_types=1);

namespace App\Expense\Domain\Entities;

use App\Expense\Domain\Enums\ExpenseItemStatus;
use App\Expense\Domain\Enums\ExpenseItemType;
use App\Expense\Domain\Exceptions\InvalidAmountException;
use App\Expense\Domain\Exceptions\ItemAlreadyApprovedException;
use App\Expense\Domain\Exceptions\ItemNotInPendingStatusException;
use App\Expense\Domain\Exceptions\MissingRejectionReasonException;
use App\Expense\Domain\ValueObjects\ExpenseItemIdValueObject;
use App\Shared\Domain\Contracts\TimestampableInterface;
use DateTimeImmutable;

final class ExpenseItem implements TimestampableInterface
{
    private ExpenseItemStatus $status = ExpenseItemStatus::PENDING;
    private ?string $evidencePath = null;
    private ?string $rejectionReason = null;
    private ?string $reviewedBy = null;
    private ?DateTimeImmutable $reviewedAt = null;

    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt = null;
    private ?DateTimeImmutable $deletedAt = null;

    private ?OperatingExpense $expense = null;

    private function __construct(
        private ExpenseItemIdValueObject $id,
        private ExpenseItemType $type,
        private float $amount,
        private bool $evidenceRequired,
        private array $ticketIds = [],
        private ?string $notes = null,
    ) {
        if ($amount <= 0) {
            throw InvalidAmountException::mustBePositive();
        }

        $this->createdAt = new DateTimeImmutable();
    }

    public static function create(
        ExpenseItemIdValueObject $id,
        ExpenseItemType $type,
        float $amount,
        array $ticketIds = [],
        ?string $notes = null,
    ): self {
        return new self(
            id: $id,
            type: $type,
            amount: $amount,
            evidenceRequired: $type->evidenceRequired(),
            ticketIds: $ticketIds,
            notes: $notes,
        );
    }

    // --- Accessors ---

    public function id(): ExpenseItemIdValueObject
    {
        return $this->id;
    }

    public function type(): ExpenseItemType
    {
        return $this->type;
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public function evidenceRequired(): bool
    {
        return $this->evidenceRequired;
    }

    public function ticketIds(): array
    {
        return $this->ticketIds;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function status(): ExpenseItemStatus
    {
        return $this->status;
    }

    public function evidencePath(): ?string
    {
        return $this->evidencePath;
    }

    public function rejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function reviewedBy(): ?string
    {
        return $this->reviewedBy;
    }

    public function reviewedAt(): ?DateTimeImmutable
    {
        return $this->reviewedAt;
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

    public function approve(string $reviewedBy): void
    {
        if ($this->status->isReviewed()) {
            throw ItemAlreadyApprovedException::create();
        }

        $this->status = ExpenseItemStatus::APPROVED;
        $this->reviewedBy = $reviewedBy;
        $this->reviewedAt = new DateTimeImmutable();
        $this->markAsUpdated();
    }

    public function reject(string $rejectionReason, string $reviewedBy): void
    {
        if ($this->status->isReviewed()) {
            throw ItemAlreadyApprovedException::create();
        }

        if (trim($rejectionReason) === '') {
            throw MissingRejectionReasonException::create();
        }

        $this->status = ExpenseItemStatus::REJECTED;
        $this->rejectionReason = $rejectionReason;
        $this->reviewedBy = $reviewedBy;
        $this->reviewedAt = new DateTimeImmutable();
        $this->markAsUpdated();
    }

    public function resetForResubmit(): void
    {
        if ($this->status !== ExpenseItemStatus::REJECTED) {
            throw ItemNotInPendingStatusException::withStatus($this->status);
        }

        $this->status = ExpenseItemStatus::PENDING;
        $this->rejectionReason = null;
        $this->reviewedBy = null;
        $this->reviewedAt = null;
        $this->markAsUpdated();
    }

    public function uploadEvidence(string $path): void
    {
        if (trim($path) === '') {
            throw new \InvalidArgumentException('La ruta de evidencia no puede estar vacía');
        }

        $this->evidencePath = $path;
        $this->markAsUpdated();
    }

    public function softDelete(): void
    {
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
