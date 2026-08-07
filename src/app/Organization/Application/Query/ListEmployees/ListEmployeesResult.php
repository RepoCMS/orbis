<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\ListEmployees;

use App\Organization\Application\Query\Response\EmployeeResponse;

final readonly class ListEmployeesResult
{
    /**
     * @param EmployeeResponse[] $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public ?int $limit,
    ) {
    }
}
