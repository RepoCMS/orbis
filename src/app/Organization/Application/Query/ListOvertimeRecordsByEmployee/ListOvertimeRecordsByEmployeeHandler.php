<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\ListOvertimeRecordsByEmployee;

use App\Organization\Application\Query\Response\OvertimeRecordResponse;
use App\Organization\Domain\Repositories\OvertimeRecordRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;

final readonly class ListOvertimeRecordsByEmployeeHandler
{
    public function __construct(
        private OvertimeRecordRepositoryInterface $overtimeRecordRepository,
    ) {
    }

    /** @return OvertimeRecordResponse[] */
    public function __invoke(ListOvertimeRecordsByEmployeeQuery $query): array
    {
        $records = $this->overtimeRecordRepository->findByEmployee(
            new EmployeeIdValueObject($query->employeeId),
        );

        return array_map(
            fn ($record) => OvertimeRecordResponse::fromEntity($record),
            $records,
        );
    }
}
