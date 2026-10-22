<?php

declare(strict_types=1);

namespace App\Client\Application\Command\UnlinkUserFromContact;

use App\Client\Domain\Entities\Contact;
use App\Client\Domain\Events\ContactUserUnlinked;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

/**
 * Desvincula el User del Contact.
 * Ahora busca al Contact por id y limpia el campo userId en el Contact.
 */
final readonly class UnlinkUserFromContactHandler
{
    public function __construct(
        private ContactRepositoryInterface   $contactRepository,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(UnlinkUserFromContactCommand $command): void
    {
        $contact = $this->contactRepository->findById(new ContactIdValueObject($command->contactId));

        if ($contact !== null && $contact->userId() !== null) {
            $userId = $contact->userId();
            $contact->unlinkUser();
            $this->contactRepository->save($contact);

            $this->eventPublisher->publish(new ContactUserUnlinked(
                contactId: $command->contactId,
                userId: (string) $userId,
            ));
        }
    }
}
