<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\RejectTechnicianLoan;

final readonly class RejectTechnicianLoanCommand
{
    public function __construct(
        public string $loanId,
        public string $reason,
        public ?string $rejectedBy = null,
    ) {
    }
}
