<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\ListEmployees;

use App\Organization\Domain\Enums\EmployeeProfile;
use App\Organization\Domain\Enums\EmployeeStatus;

final readonly class ListEmployeesQuery
{
    public function __construct(
        public ?EmployeeProfile $profile = null,
        public ?EmployeeStatus $status = null,
        public ?string $search = null,
    ) {}
}
