<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\RequestOvertimeCorrection;

use App\Organization\Domain\Exceptions\OvertimeRecordException;
use App\Organization\Domain\Repositories\OvertimeRecordRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\OvertimeRecordIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

final readonly class RequestOvertimeCorrectionHandler
{
    public function __construct(
        private OvertimeRecordRepositoryInterface $overtimeRecordRepository,
        private DomainEventPublisherInterface $publisher,
    ) {}

    public function __invoke(RequestOvertimeCorrectionCommand $command): void
    {
        $recordId = new OvertimeRecordIdValueObject($command->overtimeRecordId);
        $reviewedBy = new EmployeeIdValueObject($command->reviewedBy);

        $overtimeRecord = $this->overtimeRecordRepository->findById($recordId)
            ?? throw new OvertimeRecordException('Registro de horas extras no encontrado.');

        $overtimeRecord->requestReview($reviewedBy, $command->correctionHint);

        $this->overtimeRecordRepository->save($overtimeRecord);

        foreach ($overtimeRecord->pullDomainEvents() as $event) {
            $this->publisher->publish($event);
        }
    }
}
