<?php

declare(strict_types=1);

namespace App\Client\Application\Command\LinkUserToContact;

use App\Client\Application\Exception\ContactNotFoundException;
use App\Client\Domain\Events\ContactUserLinked;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Identity\Application\Exception\UserNotFoundException;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\Service\UserBindingCheckerInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

final readonly class LinkUserToContactHandler
{
    public function __construct(
        private ContactRepositoryInterface    $contactRepository,
        private UserRepositoryInterface       $userRepository,
        private UserBindingCheckerInterface   $bindingChecker,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(LinkUserToContactCommand $command): void
    {
        $contactId = new ContactIdValueObject($command->contactId);
        $contact = $this->contactRepository->findById($contactId);

        if ($contact === null) {
            throw ContactNotFoundException::withId($command->contactId);
        }

        $userId = new UserIdValueObject($command->userId);

        if ($this->userRepository->findById($userId) === null) {
            throw UserNotFoundException::withId($command->userId);
        }

        $this->bindingChecker->ensureNotBound($userId);

        $contact->linkUser($userId);

        $this->contactRepository->save($contact);

        $this->eventPublisher->publish(new ContactUserLinked(
            contactId: $command->contactId,
            userId: $command->userId,
        ));
    }
}
