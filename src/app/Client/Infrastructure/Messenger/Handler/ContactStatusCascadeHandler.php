<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Messenger\Handler;

use App\Client\Domain\Events\ContactDeactivated;
use App\Client\Domain\Events\ContactReactivated;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Identity\Domain\Enums\UserStatus;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Shared\Infrastructure\Messenger\Message\DomainEventMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler asíncrono para la cascada de estado Contact → User.
 *
 * REQ-CST-002:
 *   - Contact desactivado  → User vinculado pasa a INACTIVE
 *   - Contact reactivado   → User vinculado pasa a ACTIVE (si estaba INACTIVE)
 *
 * Sigue el mismo patrón que EmployeeStatusCascadeHandler.
 * No maneja ContactDeleted (ese evento no existe).
 */
#[AsMessageHandler]
final readonly class ContactStatusCascadeHandler
{
    public function __construct(
        private ContactRepositoryInterface $contactRepository,
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(DomainEventMessage $message): void
    {
        match ($message->eventClass) {
            ContactDeactivated::class => $this->handleDeactivated($message),
            ContactReactivated::class => $this->handleReactivated($message),
            default => null, // ContactDeleted no se implementa en este feature
        };
    }

    private function resolveLinkedUser(string $contactId): ?\App\Identity\Domain\Entities\User
    {
        $contact = $this->contactRepository->findById(new ContactIdValueObject($contactId));
        if ($contact === null || $contact->userId() === null) {
            return null;
        }
        return $this->userRepository->findById($contact->userId());
    }

    private function handleDeactivated(DomainEventMessage $message): void
    {
        /** @var ContactDeactivated $event */
        $event = $message->restoreEvent();
        $user = $this->resolveLinkedUser($event->contactId);

        if ($user === null || !$user->isActive()) {
            return;
        }

        $user->deactivate();
        $this->userRepository->save($user);
    }

    private function handleReactivated(DomainEventMessage $message): void
    {
        /** @var ContactReactivated $event */
        $event = $message->restoreEvent();
        $user = $this->resolveLinkedUser($event->contactId);

        if ($user === null || $user->status() !== UserStatus::INACTIVE) {
            return;
        }

        $user->reactivate();
        $this->userRepository->save($user);
    }
}
