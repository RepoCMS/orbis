<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\UpdateEmployee;

use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Enums\EmployeeProfile;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;

final readonly class UpdateEmployeeHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
    ) {
    }

    public function __invoke(UpdateEmployeeCommand $command): void
    {
        $employeeId = new EmployeeIdValueObject($command->employeeId);
        $employee   = $this->employeeRepository->findById($employeeId)
            ?? throw EmployeeNotFoundException::withId($command->employeeId);

        $employee->changeFirstName($command->firstName);
        $employee->changeLastName($command->lastName);
        $employee->changeEmail(new EmployeeEmailValueObject($command->email));
        $employee->changePhoneNumber($command->phoneNumber);
        $employee->changeProfile(EmployeeProfile::from($command->profile));

        $this->employeeRepository->save($employee);
    }
}
