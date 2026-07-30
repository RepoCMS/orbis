<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\RegisterOvertime;

use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Entities\OvertimeRecord;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\Repositories\OvertimeRecordRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\OvertimeRecordIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use DateTimeImmutable;

final readonly class RegisterOvertimeHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private OvertimeRecordRepositoryInterface $overtimeRecordRepository,
        private IdGeneratorInterface $idGenerator,
        private DomainEventPublisherInterface $publisher,
    ) {}

    public function __invoke(RegisterOvertimeCommand $command): string
    {
        $employeeId = new EmployeeIdValueObject($command->employeeId);

        $employee = $this->employeeRepository->findById($employeeId)
            ?? throw EmployeeNotFoundException::withId($command->employeeId);

        if (!$employee->isActive()) {
            throw EmployeeNotFoundException::withId($command->employeeId);
        }

        $id = new OvertimeRecordIdValueObject($this->idGenerator->generate());

        $overtimeRecord = OvertimeRecord::register(
            id: $id,
            employeeId: $employeeId,
            workedAt: new DateTimeImmutable($command->workedAt),
            startTime: new DateTimeImmutable($command->startTime),
            endTime: new DateTimeImmutable($command->endTime),
            reason: $command->reason,
            evidenceUrl: $command->evidenceUrl,
        );

        $this->overtimeRecordRepository->save($overtimeRecord);

        foreach ($overtimeRecord->pullDomainEvents() as $event) {
            $this->publisher->publish($event);
        }

        return (string) $id;
    }
}
