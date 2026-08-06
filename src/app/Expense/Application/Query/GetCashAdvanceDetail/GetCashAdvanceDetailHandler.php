<?php

declare(strict_types=1);

namespace App\Expense\Application\Query\GetCashAdvanceDetail;

use App\Expense\Application\Query\Response\CashAdvanceDetailResponse;
use App\Expense\Domain\Repositories\CashAdvanceRepositoryInterface;
use App\Expense\Domain\ValueObjects\CashAdvanceIdValueObject;

final readonly class GetCashAdvanceDetailHandler
{
    public function __construct(
        private CashAdvanceRepositoryInterface $cashAdvanceRepo,
    ) {
    }

    public function __invoke(GetCashAdvanceDetailQuery $query): ?CashAdvanceDetailResponse
    {
        $cashAdvance = $this->cashAdvanceRepo->findById(
            new CashAdvanceIdValueObject($query->cashAdvanceId)
        );

        if ($cashAdvance === null) {
            return null;
        }

        return CashAdvanceDetailResponse::fromEntity($cashAdvance);
    }
}
