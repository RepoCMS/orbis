<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\CreateEmployee;

use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Enums\EmployeeProfile;
use App\Organization\Domain\Events\EmployeeCreated;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class CreateEmployeeHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private IdGeneratorInterface $idGenerator,
        private DomainEventPublisherInterface $publisher,
    ) {
    }

    public function __invoke(CreateEmployeeCommand $command): string
    {
        $id = $this->idGenerator->generate();

        $hiredAt = \DateTimeImmutable::createFromFormat('Y-m-d', $command->hiredAt)
            ?: throw new \InvalidArgumentException('Fecha de contratación inválida: ' . $command->hiredAt);

        $employee = Employee::hire(
            id:                   new EmployeeIdValueObject($id),
            identificationNumber: new EmployeeIdentificationNumberValueObject($command->identificationNumber),
            firstName:            $command->firstName,
            lastName:             $command->lastName,
            email:                new EmployeeEmailValueObject($command->email),
            phoneNumber:          $command->phoneNumber,
            hiredAt:              $hiredAt,
            profile:              EmployeeProfile::from($command->profile),
        );

        $this->employeeRepository->save($employee);

        $this->publisher->publish(new EmployeeCreated(
            employeeId: $id,
            firstName:  $command->firstName,
            lastName:   $command->lastName,
            email:      $command->email,
        ));

        return $id;
    }
}
