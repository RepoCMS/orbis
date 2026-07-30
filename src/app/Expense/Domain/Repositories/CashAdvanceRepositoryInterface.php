<?php

declare(strict_types=1);

namespace App\Expense\Domain\Repositories;

use App\Expense\Domain\Entities\CashAdvance;
use App\Expense\Domain\Enums\CashAdvanceStatus;
use App\Expense\Domain\ValueObjects\CashAdvanceIdValueObject;

interface CashAdvanceRepositoryInterface
{
    public function findById(CashAdvanceIdValueObject $id): ?CashAdvance;

    /** @return CashAdvance[] */
    public function findByEmployeeId(string $employeeId): array;

    /** @return CashAdvance[] */
    public function findByStatus(CashAdvanceStatus $status): array;

    /** @return CashAdvance[] */
    public function findOverdue(int $days): array;

    /** @return CashAdvance[] */
    public function findAll(): array;

    public function nextAdvanceNumber(int $year): string;

    public function save(CashAdvance $cashAdvance): void;

    public function delete(CashAdvanceIdValueObject $id): void;
}
