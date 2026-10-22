<?php

declare(strict_types=1);

namespace App\Client\Application\Command\DeactivateTerminalAssignment;

use App\Client\Application\Exception\TerminalAssignmentNotFoundException;
use App\Client\Domain\Events\TerminalAssignmentDeactivated;
use App\Client\Domain\Repositories\TerminalAssignmentRepositoryInterface;
use App\Client\Domain\Repositories\TerminalRepositoryInterface;
use App\Client\Domain\ValueObjects\TerminalAssignmentIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

final readonly class DeactivateTerminalAssignmentHandler
{
    public function __construct(
        private TerminalAssignmentRepositoryInterface $assignmentRepository,
        private TerminalRepositoryInterface $terminalRepository,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(DeactivateTerminalAssignmentCommand $command): void
    {
        $assignmentId = new TerminalAssignmentIdValueObject($command->id);
        $assignment = $this->assignmentRepository->findById($assignmentId)
            ?? throw TerminalAssignmentNotFoundException::withId($command->id);

        $assignment->deactivate();

        $this->assignmentRepository->save($assignment);

        $terminal = $this->terminalRepository->findById($assignment->terminalId());

        $this->eventPublisher->publish(new TerminalAssignmentDeactivated(
            assignmentId: (string) $assignment->id(),
            terminalId:   (string) $assignment->terminalId(),
            clientId:     (string) $terminal->clientId(),
        ));
    }
}
