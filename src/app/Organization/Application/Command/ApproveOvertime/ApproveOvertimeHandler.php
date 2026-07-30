<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\ApproveOvertime;

use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Exceptions\OvertimeRecordException;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\Repositories\OvertimeRecordRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\OvertimeRecordIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

final readonly class ApproveOvertimeHandler
{
    public function __construct(
        private OvertimeRecordRepositoryInterface $overtimeRecordRepository,
        private EmployeeRepositoryInterface $employeeRepository,
        private DomainEventPublisherInterface $publisher,
    ) {}

    public function __invoke(ApproveOvertimeCommand $command): void
    {
        $recordId = new OvertimeRecordIdValueObject($command->overtimeRecordId);
        $reviewedBy = new EmployeeIdValueObject($command->reviewedBy);

        $overtimeRecord = $this->overtimeRecordRepository->findById($recordId)
            ?? throw new OvertimeRecordException('Registro de horas extras no encontrado.');

        $reviewer = $this->employeeRepository->findById($reviewedBy)
            ?? throw EmployeeNotFoundException::withId($command->reviewedBy);

        $overtimeRecord->approve($reviewedBy);

        $this->overtimeRecordRepository->save($overtimeRecord);

        foreach ($overtimeRecord->pullDomainEvents() as $event) {
            $this->publisher->publish($event);
        }
    }
}
