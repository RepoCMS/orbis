<?php

declare(strict_types=1);

namespace App\Expense\Application\Command\AddExpenseItem;

final readonly class AddExpenseItemCommand
{
    public function __construct(
        public string $expenseId,
        public string $type,
        public float $amount,
        public array $ticketIds = [],
        public ?string $notes = null,
    ) {
    }
}
