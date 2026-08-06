<?php

declare(strict_types=1);

namespace App\Support\Application\Command\RecordModuleFinding;

use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Application\Exception\TicketNotFoundException;
use App\Support\Domain\AtmModel\FindingType;
use App\Support\Domain\AtmModel\ModulePathValueObject;
use App\Support\Domain\Entities\TicketModuleFinding;
use App\Support\Domain\Repositories\TicketModuleFindingRepositoryInterface;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketModuleFindingIdValueObject;

final readonly class RecordModuleFindingHandler
{
    public function __construct(
        private TicketRepositoryInterface                $ticketRepo,
        private TicketModuleFindingRepositoryInterface   $findingRepo,
        private IdGeneratorInterface                     $idGenerator,
        private DomainEventPublisherInterface            $eventPublisher,
    ) {
    }

    public function __invoke(RecordModuleFindingCommand $command): string
    {
        $ticketId = TicketIdValueObject::fromString($command->ticketId);

        $ticket = $this->ticketRepo->findById($ticketId);
        if ($ticket === null) {
            throw TicketNotFoundException::withId($command->ticketId);
        }

        $finding = TicketModuleFinding::record(
            id: TicketModuleFindingIdValueObject::fromString($this->idGenerator->generate()),
            ticketId: $ticketId,
            modulePath: new ModulePathValueObject($command->modulePath),
            componentName: $command->componentName,
            findingType: FindingType::from($command->findingType),
            registeredBy: $command->registeredBy,
            notes: $command->notes,
        );

        $ticket->recordModuleFinding($finding);

        $this->findingRepo->save($finding);
        $this->ticketRepo->save($ticket);

        foreach ($ticket->pullDomainEvents() as $event) {
            $this->eventPublisher->publish($event);
        }

        return (string) $finding->id();
    }
}
