<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\Response;

use App\Organization\Domain\Entities\Employee;

final readonly class EmployeeResponse
{
    public function __construct(
        public string $id,
        public ?string $userId,
        public string $identificationNumber,
        public string $firstName,
        public string $lastName,
        public string $fullName,
        public string $email,
        public string $phoneNumber,
        public string $hiredAt,
        public string $status,
        public string $profile,
        public ?string $serviceZoneId,
    ) {
    }

    /**
     * @param Employee $employee
     *
     * @return EmployeeResponse
     */
    public static function fromEntity(Employee $employee): self
    {
        return new self(
            id:                   (string) $employee->id(),
            userId:               $employee->userId() !== null ? (string) $employee->userId() : null,
            identificationNumber: (string) $employee->identificationNumber(),
            firstName:            $employee->firstName(),
            lastName:             $employee->lastName(),
            fullName:             $employee->fullName(),
            email:                (string) $employee->email(),
            phoneNumber:          $employee->phoneNumber(),
            hiredAt:              $employee->hiredAt()->format('Y-m-d'),
            status:               $employee->status()->value,
            profile:              $employee->profile()->value,
            serviceZoneId:        $employee->serviceZoneId() !== null ? (string) $employee->serviceZoneId() : null,
        );
    }
}
