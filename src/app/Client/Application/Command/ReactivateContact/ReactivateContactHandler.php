<?php

declare(strict_types=1);

namespace App\Client\Application\Command\ReactivateContact;

use App\Client\Application\Exception\ContactNotFoundException;
use App\Client\Domain\Events\ContactReactivated;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

final readonly class ReactivateContactHandler
{
    public function __construct(
        private ContactRepositoryInterface $contactRepository,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(ReactivateContactCommand $command): void
    {
        $contactId = new ContactIdValueObject($command->contactId);
        $contact = $this->contactRepository->findById($contactId)
            ?? throw ContactNotFoundException::withId($command->contactId);

        $contact->reactivate();

        $this->contactRepository->save($contact);

        $this->eventPublisher->publish(new ContactReactivated(
            contactId: $command->contactId,
            clientId: (string) $contact->clientId(),
        ));
    }
}
