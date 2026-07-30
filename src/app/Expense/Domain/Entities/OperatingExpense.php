<?php

declare(strict_types=1);

namespace App\Expense\Domain\Entities;

use App\Expense\Domain\Enums\ExpenseItemStatus;
use App\Expense\Domain\Enums\ExpenseStatus;
use App\Expense\Domain\Events\ExpenseApproved;
use App\Expense\Domain\Events\ExpenseItemRejected;
use App\Expense\Domain\Events\ExpenseResubmitted;
use App\Expense\Domain\Events\ExpenseSubmitted;
use App\Expense\Domain\Exceptions\ExpenseHasNoItemsException;
use App\Expense\Domain\Exceptions\ExpenseHasPendingItemsException;
use App\Expense\Domain\Exceptions\ExpenseHasRejectedItemsException;
use App\Expense\Domain\Exceptions\ExpenseNotEditableException;
use App\Expense\Domain\Exceptions\ExpenseNotSubmittedException;
use App\Expense\Domain\Exceptions\MissingEvidenceException;
use App\Expense\Domain\ValueObjects\ExpenseItemIdValueObject;
use App\Expense\Domain\ValueObjects\OperatingExpenseIdValueObject;
use App\Shared\Domain\Contracts\TimestampableInterface;
use App\Shared\Domain\Event\DomainEvent;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

final class OperatingExpense implements TimestampableInterface
{
    /** @var DomainEvent[] */
    private array $domainEvents = [];

    /** @var Collection<int, ExpenseItem> */
    private Collection $items;

    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt = null;
    private ?DateTimeImmutable $submittedAt = null;
    private ?string $approvedBy = null;
    private ?DateTimeImmutable $approvedAt = null;
    private ?DateTimeImmutable $deletedAt = null;

    private function __construct(
        private OperatingExpenseIdValueObject $id,
        private int $sequence,
        private string $employeeId,
        private string $reason,
        private DateTimeImmutable $startDate,
        private DateTimeImmutable $endDate,
        private ExpenseStatus $status = ExpenseStatus::DRAFT,
        private ?string $cashAdvanceId = null,
        private array $ticketIds = [],
    ) {
        $this->createdAt = new DateTimeImmutable();
        $this->items = new ArrayCollection();
    }

    public static function create(
        OperatingExpenseIdValueObject $id,
        int $sequence,
        string $employeeId,
        string $reason,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        ?string $cashAdvanceId = null,
        array $ticketIds = [],
    ): self {
        if (trim($employeeId) === '') {
            throw new \InvalidArgumentException('El empleado es obligatorio');
        }
        if (trim($reason) === '') {
            throw new \InvalidArgumentException('El motivo es obligatorio');
        }
        if ($startDate > $endDate) {
            throw new \InvalidArgumentException('La fecha de inicio no puede ser posterior a la fecha de fin');
        }
        if ($sequence < 1) {
            throw new \InvalidArgumentException('La secuencia debe ser un número positivo');
        }

        return new self(
            id: $id,
            sequence: $sequence,
            employeeId: $employeeId,
            reason: $reason,
            startDate: $startDate,
            endDate: $endDate,
            status: ExpenseStatus::DRAFT,
            cashAdvanceId: $cashAdvanceId,
            ticketIds: $ticketIds,
        );
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

    public function id(): OperatingExpenseIdValueObject
    {
        return $this->id;
    }

    public function sequence(): int
    {
        return $this->sequence;
    }

    public function employeeId(): string
    {
        return $this->employeeId;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function startDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function endDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    public function status(): ExpenseStatus
    {
        return $this->status;
    }

    public function cashAdvanceId(): ?string
    {
        return $this->cashAdvanceId;
    }

    public function ticketIds(): array
    {
        return $this->ticketIds;
    }

    /** @return ExpenseItem[] */
    public function items(): array
    {
        return $this->items->toArray();
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function submittedAt(): ?DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function approvedBy(): ?string
    {
        return $this->approvedBy;
    }

    public function approvedAt(): ?DateTimeImmutable
    {
        return $this->approvedAt;
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

    public function addItem(ExpenseItem $item): void
    {
        if (!$this->status->canEdit()) {
            throw ExpenseNotEditableException::withStatus($this->status);
        }

        $this->items->add($item);
        $this->markAsUpdated();
    }

    public function submit(): void
    {
        if ($this->status !== ExpenseStatus::DRAFT && $this->status !== ExpenseStatus::REJECTED) {
            throw new \InvalidArgumentException(
                sprintf('No se puede enviar una liquidación con estado %s', $this->status->value)
            );
        }

        if ($this->items->count() === 0) {
            throw ExpenseHasNoItemsException::create();
        }

        // Validar que todos los items con evidenceRequired tengan evidencia
        $missingEvidence = [];
        foreach ($this->items as $item) {
            if ($item->evidenceRequired() && $item->evidencePath() === null) {
                $missingEvidence[] = $item;
            }
        }
        if (count($missingEvidence) > 0) {
            throw MissingEvidenceException::forMultipleItems(count($missingEvidence));
        }

        $wasRejected = $this->status === ExpenseStatus::REJECTED;
        $this->status = ExpenseStatus::SUBMITTED;
        $this->submittedAt = new DateTimeImmutable();

        if ($wasRejected) {
            $this->recordEvent(new ExpenseResubmitted(
                expenseId: (string) $this->id,
                employeeId: $this->employeeId,
                totalItems: $this->items->count(),
                totalAmount: $this->totalExpenses(),
            ));
        } else {
            $this->recordEvent(new ExpenseSubmitted(
                expenseId: (string) $this->id,
                employeeId: $this->employeeId,
                totalItems: $this->items->count(),
                totalAmount: $this->totalExpenses(),
            ));
        }

        $this->markAsUpdated();
    }

    /**
     * Reenvía una liquidación rechazada, reseteando los items rechazados a PENDING.
     */
    public function resubmit(): void
    {
        if ($this->status !== ExpenseStatus::REJECTED) {
            throw new \InvalidArgumentException('Solo se puede reenviar una liquidación rechazada');
        }

        if ($this->items->count() === 0) {
            throw ExpenseHasNoItemsException::create();
        }

        // Resetear items rechazados a pending para nueva revisión
        foreach ($this->items as $item) {
            if ($item->status() === ExpenseItemStatus::REJECTED) {
                $item->resetForResubmit();
            }
        }

        // Validar evidencia obligatoria nuevamente
        $missingEvidence = [];
        foreach ($this->items as $item) {
            if ($item->evidenceRequired() && $item->evidencePath() === null) {
                $missingEvidence[] = $item;
            }
        }
        if (count($missingEvidence) > 0) {
            throw MissingEvidenceException::forMultipleItems(count($missingEvidence));
        }

        $this->status = ExpenseStatus::SUBMITTED;
        $this->submittedAt = new DateTimeImmutable();

        $this->recordEvent(new ExpenseResubmitted(
            expenseId: (string) $this->id,
            employeeId: $this->employeeId,
            totalItems: $this->items->count(),
            totalAmount: $this->totalExpenses(),
        ));

        $this->markAsUpdated();
    }

    public function approveItem(string $itemId, string $reviewedBy): void
    {
        $item = $this->findItem($itemId);
        if ($item === null) {
            throw new \InvalidArgumentException('Item de gasto no encontrado');
        }

        $item->approve($reviewedBy);
        $this->markAsUpdated();
    }

    public function rejectItem(string $itemId, string $rejectionReason, string $reviewedBy): void
    {
        $item = $this->findItem($itemId);
        if ($item === null) {
            throw new \InvalidArgumentException('Item de gasto no encontrado');
        }

        $item->reject($rejectionReason, $reviewedBy);

        $this->status = ExpenseStatus::REJECTED;

        $this->recordEvent(new ExpenseItemRejected(
            expenseId: (string) $this->id,
            itemId: $itemId,
            rejectionReason: $rejectionReason,
            reviewedBy: $reviewedBy,
        ));

        $this->markAsUpdated();
    }

    /**
     * Marca un item como revisado y, si todos están revisados sin rechazos,
     * aprueba automáticamente la liquidación.
     */
    public function markItemReviewed(string $itemId, string $reviewedBy): void
    {
        if (!$this->status->canApprove()) {
            throw ExpenseNotSubmittedException::withStatus($this->status);
        }

        $item = $this->findItem($itemId);
        if ($item === null) {
            throw new \InvalidArgumentException('Item de gasto no encontrado');
        }

        if (!$item->status()->isPending()) {
            throw new \InvalidArgumentException('El item ya fue revisado');
        }

        $item->approve($reviewedBy);

        // Verificar si todos los items están revisados sin rechazos
        $allReviewed = true;
        $hasRejected = false;
        foreach ($this->items as $i) {
            if ($i->status()->isPending()) {
                $allReviewed = false;
                break;
            }
            if ($i->status()->isRejected()) {
                $hasRejected = true;
            }
        }

        if ($allReviewed && !$hasRejected) {
            $this->status = ExpenseStatus::APPROVED;
            $this->approvedBy = $reviewedBy;
            $this->approvedAt = new DateTimeImmutable();

            $this->recordEvent(new ExpenseApproved(
                expenseId: (string) $this->id,
                approvedBy: $reviewedBy,
                totalAmount: $this->totalExpenses(),
            ));
        }

        $this->markAsUpdated();
    }

    public function approve(string $approvedBy): void
    {
        if (!$this->status->canApprove()) {
            throw ExpenseNotSubmittedException::withStatus($this->status);
        }

        if ($this->items->count() === 0) {
            throw ExpenseHasNoItemsException::create();
        }

        foreach ($this->items as $item) {
            if ($item->status()->isRejected()) {
                throw ExpenseHasRejectedItemsException::create();
            }
            if ($item->status()->isPending()) {
                throw ExpenseHasPendingItemsException::create();
            }
        }

        $this->status = ExpenseStatus::APPROVED;
        $this->approvedBy = $approvedBy;
        $this->approvedAt = new DateTimeImmutable();

        $this->recordEvent(new ExpenseApproved(
            expenseId: (string) $this->id,
            approvedBy: $approvedBy,
            totalAmount: $this->totalExpenses(),
        ));

        $this->markAsUpdated();
    }

    public function totalExpenses(): float
    {
        return array_sum(array_map(fn(ExpenseItem $item) => $item->amount(), $this->items->toArray()));
    }

    public function updateDetails(string $reason, DateTimeImmutable $startDate, DateTimeImmutable $endDate): void
    {
        if (!$this->status->isDraft()) {
            throw ExpenseNotEditableException::withStatus($this->status);
        }

        if (trim($reason) === '') {
            throw new \InvalidArgumentException('El motivo es obligatorio');
        }

        if ($startDate > $endDate) {
            throw new \InvalidArgumentException('La fecha de inicio no puede ser posterior a la fecha de fin');
        }

        $this->reason = $reason;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->markAsUpdated();
    }

    public function softDelete(): void
    {
        if (!$this->status->isDraft()) {
            throw new \InvalidArgumentException('Solo se puede eliminar una liquidación en estado borrador');
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

    private function findItem(string $itemId): ?ExpenseItem
    {
        foreach ($this->items as $item) {
            if ((string) $item->id() === $itemId) {
                return $item;
            }
        }
        return null;
    }

    private function markAsUpdated(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
