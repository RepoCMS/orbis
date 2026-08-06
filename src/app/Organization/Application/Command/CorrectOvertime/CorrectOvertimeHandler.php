<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\CorrectOvertime;

use App\Organization\Domain\Exceptions\OvertimeRecordException;
use App\Organization\Domain\Repositories\OvertimeRecordRepositoryInterface;
use App\Organization\Domain\ValueObjects\OvertimeRecordIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use DateTimeImmutable;

final readonly class CorrectOvertimeHandler
{
    public function __construct(
        private OvertimeRecordRepositoryInterface $overtimeRecordRepository,
        private DomainEventPublisherInterface $publisher,
    ) {
    }

    public function __invoke(CorrectOvertimeCommand $command): void
    {
        $recordId = new OvertimeRecordIdValueObject($command->overtimeRecordId);

        $overtimeRecord = $this->overtimeRecordRepository->findById($recordId)
            ?? throw new OvertimeRecordException('Registro de horas extras no encontrado.');

        $overtimeRecord->correct(
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
    }
}
