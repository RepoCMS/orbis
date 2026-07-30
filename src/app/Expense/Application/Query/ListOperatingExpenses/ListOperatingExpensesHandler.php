<?php

declare(strict_types=1);

namespace App\Expense\Application\Query\ListOperatingExpenses;

use App\Expense\Application\Query\Response\OperatingExpenseResponse;
use App\Expense\Domain\Repositories\OperatingExpenseRepositoryInterface;
use DateTimeImmutable;

final readonly class ListOperatingExpensesHandler
{
    public function __construct(
        private OperatingExpenseRepositoryInterface $expenseRepo,
    ) {}

    /**
     * @return OperatingExpenseResponse[]
     */
    public function __invoke(ListOperatingExpensesQuery $query): array
    {
        $expenses = $this->expenseRepo->findAll();

        if ($query->employeeId !== null) {
            $expenses = array_filter(
                $expenses,
                fn ($e) => $e->employeeId() === $query->employeeId
            );
        }

        if ($query->status !== null) {
            $expenses = array_filter(
                $expenses,
                fn ($e) => $e->status()->value === $query->status
            );
        }

        if ($query->dateFrom !== null) {
            $dateFrom = new DateTimeImmutable($query->dateFrom);
            $expenses = array_filter(
                $expenses,
                fn ($e) => $e->startDate() >= $dateFrom
            );
        }

        if ($query->dateTo !== null) {
            $dateTo = new DateTimeImmutable($query->dateTo);
            $expenses = array_filter(
                $expenses,
                fn ($e) => $e->endDate() <= $dateTo
            );
        }

        if ($query->ticketId !== null) {
            $expenses = array_filter(
                $expenses,
                fn ($e) => in_array($query->ticketId, $e->ticketIds(), true)
            );
        }

        return array_map(
            fn ($expense) => OperatingExpenseResponse::fromEntity($expense),
            array_values($expenses)
        );
    }
}
