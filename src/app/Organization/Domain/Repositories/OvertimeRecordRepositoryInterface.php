<?php

declare(strict_types=1);

namespace App\Organization\Domain\Repositories;

use App\Organization\Domain\Entities\OvertimeRecord;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\OvertimeRecordIdValueObject;

interface OvertimeRecordRepositoryInterface
{
    public function save(OvertimeRecord $record): void;

    public function findById(OvertimeRecordIdValueObject $id): ?OvertimeRecord;

    /**
     * @return OvertimeRecord[]
     */
    public function findByEmployee(EmployeeIdValueObject $employeeId): array;

    /**
     * @return OvertimeRecord[]
     */
    public function findPendingByEmployee(EmployeeIdValueObject $employeeId): array;
}
