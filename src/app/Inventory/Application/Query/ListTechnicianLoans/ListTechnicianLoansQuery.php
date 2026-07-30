<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\ListTechnicianLoans;

final readonly class ListTechnicianLoansQuery
{
    public function __construct(
        public ?string $status = null,
        public ?string $technicianId = null,
    ) {
    }
}
