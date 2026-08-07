<?php

declare(strict_types=1);

namespace App\Client\Application\Command\DeactivateContact;

use App\Client\Application\Exception\ContactNotFoundException;
use App\Client\Domain\Events\ContactDeactivated;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

final readonly class DeactivateContactHandler
{
    public function __construct(
        private ContactRepositoryInterface $contactRepository,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(DeactivateContactCommand $command): void
    {
        $contactId = new ContactIdValueObject($command->contactId);
        $contact = $this->contactRepository->findById($contactId)
            ?? throw ContactNotFoundException::withId($command->contactId);

        $contact->deactivate();

        $this->contactRepository->save($contact);

        $this->eventPublisher->publish(new ContactDeactivated(
            contactId: $command->contactId,
            clientId: (string) $contact->clientId(),
        ));
    }
}
