<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\ApproveTechnicianLoan;

final readonly class ApproveTechnicianLoanCommand
{
    public function __construct(
        public string $loanId,
        public string $approvedBy,
        public ?string $movementId = null,
    ) {
    }
}
