<?php

declare(strict_types=1);

namespace App\Support\Application\Command\CreateRmaRecord;

use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Domain\Entities\RmaRecord;
use App\Support\Domain\Repositories\RmaRecordRepositoryInterface;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\RmaRecordIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

final readonly class CreateRmaRecordHandler
{
    public function __construct(
        private RmaRecordRepositoryInterface $rmaRecordRepo,
        private TicketRepositoryInterface    $ticketRepo,
        private IdGeneratorInterface         $idGenerator,
    ) {}

    public function __invoke(CreateRmaRecordCommand $command): string
    {
        $ticketId = TicketIdValueObject::fromString($command->ticketId);

        $ticket = $this->ticketRepo->findById($ticketId);
        if ($ticket === null) {
            throw new \App\Support\Application\Exception\TicketNotFoundException($command->ticketId);
        }

        $record = RmaRecord::create(
            id:           RmaRecordIdValueObject::fromString($this->idGenerator->generate()),
            ticketId:     $command->ticketId,
            productCode:  $command->productCode,
            createdBy:    $command->createdBy,
            serialNumber: $command->serialNumber,
            quantity:     $command->quantity,
            notes:        $command->notes,
        );

        $this->rmaRecordRepo->save($record);

        return (string) $record->id();
    }
}
