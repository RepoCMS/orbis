<?php

declare(strict_types=1);

namespace App\Client\Application\Command\UpdateContact;

use App\Client\Application\Exception\ContactNotFoundException;
use App\Client\Domain\Events\ContactUpdated;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ContactEmailValueObject;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Client\Domain\ValueObjects\ContactPhoneNumberValueObject;
use App\Client\Domain\ValueObjects\ContactRoleValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

final readonly class UpdateContactHandler
{
    public function __construct(
        private ContactRepositoryInterface $contactRepository,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(UpdateContactCommand $command): void
    {
        $contactId = new ContactIdValueObject($command->contactId);
        $contact = $this->contactRepository->findById($contactId)
            ?? throw ContactNotFoundException::withId($command->contactId);

        $contact->changeName($command->firstName, $command->lastName);
        $contact->changeEmail(new ContactEmailValueObject($command->email));
        $contact->changePhoneNumber(new ContactPhoneNumberValueObject($command->phoneNumber));
        $contact->changeRole(new ContactRoleValueObject($command->role));

        $this->contactRepository->save($contact);

        $this->eventPublisher->publish(new ContactUpdated(
            contactId: $command->contactId,
            clientId:  (string) $contact->clientId(),
        ));
    }
}
