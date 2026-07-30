<?php

declare(strict_types=1);

namespace App\Client\Application\Command\UpdateTerminalAssignment;

use App\Client\Application\Exception\TerminalAssignmentNotFoundException;
use App\Client\Domain\Events\TerminalAssignmentUpdated;
use App\Client\Domain\Repositories\TerminalAssignmentRepositoryInterface;
use App\Client\Domain\ValueObjects\SiteIdValueObject;
use App\Client\Domain\ValueObjects\TerminalAssignmentIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

final readonly class UpdateTerminalAssignmentHandler
{
    public function __construct(
        private TerminalAssignmentRepositoryInterface $assignmentRepository,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(UpdateTerminalAssignmentCommand $command): void
    {
        $assignmentId = new TerminalAssignmentIdValueObject($command->id);
        $assignment = $this->assignmentRepository->findById($assignmentId)
            ?? throw TerminalAssignmentNotFoundException::withId($command->id);

        $assignment->changeName($command->name);
        $assignment->changeIdAssingedByClient($command->idAssignedByClient);
        $assignment->changeSiteId(new SiteIdValueObject($command->locationId));

        $this->assignmentRepository->save($assignment);

        $this->eventPublisher->publish(new TerminalAssignmentUpdated(
            assignmentId:      (string) $assignment->id(),
            terminalId:        (string) $assignment->terminalId(),
            locationId:        (string) $assignment->siteId(),
            name:              (string) $assignment->name(),
            idAssignedByClient: (string) $assignment->idAssingedByClient(),
        ));
    }
}
