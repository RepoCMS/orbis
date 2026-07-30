<?php

declare(strict_types=1);

namespace App\Client\Application\Command\RegisterTerminalAssignment;

use App\Client\Application\Exception\TerminalNotFoundException;
use App\Client\Domain\Entities\TerminalAssignment;
use App\Client\Domain\Events\TerminalAssignmentRegistered;
use App\Client\Domain\Repositories\TerminalAssignmentRepositoryInterface;
use App\Client\Domain\Repositories\TerminalRepositoryInterface;
use App\Client\Domain\ValueObjects\SiteIdValueObject;
use App\Client\Domain\ValueObjects\TerminalAssignmentIdValueObject;
use App\Client\Domain\ValueObjects\TerminalIdAssignedByClientValueObject;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Client\Domain\ValueObjects\TerminalNameValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

final readonly class RegisterTerminalAssignmentHandler
{
    public function __construct(
        private TerminalRepositoryInterface $terminalRepository,
        private TerminalAssignmentRepositoryInterface $assignmentRepository,
        private IdGeneratorInterface $idGenerator,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(RegisterTerminalAssignmentCommand $command): string
    {
        $terminalId = new TerminalIdValueObject($command->terminalId);
        $terminal = $this->terminalRepository->findById($terminalId)
            ?? throw TerminalNotFoundException::withId($command->terminalId);

        $assignmentId = new TerminalAssignmentIdValueObject($this->idGenerator->generate());

        $assignment = TerminalAssignment::add(
            id:                  $assignmentId,
            terminalId:          $terminalId,
            name:                new TerminalNameValueObject($command->name),
            idAssingedByClient:  new TerminalIdAssignedByClientValueObject($command->idAssignedByClient),
            siteId:              new SiteIdValueObject($command->locationId),
        );

        $this->assignmentRepository->save($assignment);

        $this->eventPublisher->publish(new TerminalAssignmentRegistered(
            assignmentId: (string) $assignment->id(),
            terminalId:   $command->terminalId,
            locationId:   $command->locationId,
        ));

        return (string) $assignment->id();
    }
}
