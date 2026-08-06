<?php

declare(strict_types=1);

namespace App\Expense\Application\Query\ListCashAdvances;

use App\Expense\Application\Query\Response\CashAdvanceResponse;
use App\Expense\Domain\Repositories\CashAdvanceRepositoryInterface;
use DateTimeImmutable;

final readonly class ListCashAdvancesHandler
{
    public function __construct(
        private CashAdvanceRepositoryInterface $cashAdvanceRepo,
    ) {
    }

    /**
     * @return CashAdvanceResponse[]
     */
    public function __invoke(ListCashAdvancesQuery $query): array
    {
        $advances = $this->cashAdvanceRepo->findAll();

        if ($query->employeeId !== null) {
            $advances = array_filter(
                $advances,
                fn ($a) => $a->employeeId() === $query->employeeId
            );
        }

        if ($query->status !== null) {
            $advances = array_filter(
                $advances,
                fn ($a) => $a->status()->value === $query->status
            );
        }

        if ($query->daysOverdue !== null) {
            $now = new DateTimeImmutable();
            $advances = array_filter(
                $advances,
                fn ($a) => $now->diff($a->givenAt())->days > $query->daysOverdue
            );
        }

        return array_map(
            fn ($advance) => CashAdvanceResponse::fromEntity($advance),
            array_values($advances)
        );
    }
}
