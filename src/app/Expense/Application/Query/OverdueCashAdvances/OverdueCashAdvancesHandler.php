<?php

declare(strict_types=1);

namespace App\Expense\Application\Query\OverdueCashAdvances;

use App\Expense\Application\Query\Response\CashAdvanceResponse;
use App\Expense\Domain\Enums\CashAdvanceStatus;
use App\Expense\Domain\Repositories\CashAdvanceRepositoryInterface;
use DateTimeImmutable;

final readonly class OverdueCashAdvancesHandler
{
    public function __construct(
        private CashAdvanceRepositoryInterface $cashAdvanceRepo,
    ) {
    }

    /**
     * @return CashAdvanceResponse[]
     */
    public function __invoke(OverdueCashAdvancesQuery $query): array
    {
        $now = new DateTimeImmutable();
        $advances = $this->cashAdvanceRepo->findByStatus(CashAdvanceStatus::OUTSTANDING);

        return array_values(
            array_map(
                fn ($advance) => CashAdvanceResponse::fromEntity($advance),
                array_filter(
                    $advances,
                    fn ($a) => $now->diff($a->givenAt())->days > $query->cutoffDays
                )
            )
        );
    }
}
