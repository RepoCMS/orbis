<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CancelTechnicianLoan;

final readonly class CancelTechnicianLoanCommand
{
    public function __construct(
        public string $loanId,
        public ?string $cancelledBy = null,
    ) {
    }
}
