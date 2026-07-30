<?php

declare(strict_types=1);

namespace App\Support\Application\Command\AddEvidence;

use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Application\Exception\TicketNotFoundException;
use App\Support\Domain\Entities\TicketEvidence;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketEvidenceIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

final readonly class AddEvidenceHandler
{
    public function __construct(
        private TicketRepositoryInterface $ticketRepo,
        private IdGeneratorInterface      $idGenerator,
    ) {}

    public function __invoke(AddEvidenceCommand $command): void
    {
        $ticketId = TicketIdValueObject::fromString($command->ticketId);

        $ticket = $this->ticketRepo->findById($ticketId);
        if ($ticket === null) {
            throw TicketNotFoundException::withId($command->ticketId);
        }

        $evidence = TicketEvidence::upload(
            id:         TicketEvidenceIdValueObject::fromString($this->idGenerator->generate()),
            ticketId:   $ticketId,
            filePath:   $command->filePath,
            uploadedBy: $command->uploadedBy,
        );

        $ticket->addEvidence($evidence);

        $this->ticketRepo->save($ticket);
    }
}
