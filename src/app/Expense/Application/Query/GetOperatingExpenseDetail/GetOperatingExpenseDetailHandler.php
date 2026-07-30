<?php

declare(strict_types=1);

namespace App\Expense\Application\Query\GetOperatingExpenseDetail;

use App\Expense\Application\Query\Response\OperatingExpenseResponse;
use App\Expense\Domain\Repositories\OperatingExpenseRepositoryInterface;
use App\Expense\Domain\ValueObjects\OperatingExpenseIdValueObject;

final readonly class GetOperatingExpenseDetailHandler
{
    public function __construct(
        private OperatingExpenseRepositoryInterface $expenseRepo,
    ) {}

    public function __invoke(GetOperatingExpenseDetailQuery $query): ?OperatingExpenseResponse
    {
        $expense = $this->expenseRepo->findById(
            new OperatingExpenseIdValueObject($query->expenseId)
        );

        if ($expense === null) {
            return null;
        }

        return OperatingExpenseResponse::fromEntity($expense);
    }
}
