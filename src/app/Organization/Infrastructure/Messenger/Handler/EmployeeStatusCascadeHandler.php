<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Messenger\Handler;

use App\Identity\Domain\Enums\UserStatus;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Organization\Domain\Events\EmployeeDeactivated;
use App\Organization\Domain\Events\EmployeeDeleted;
use App\Organization\Domain\Events\EmployeeReactivated;
use App\Shared\Infrastructure\DbExceptionHandlerTrait;
use App\Shared\Infrastructure\Messenger\Message\DomainEventMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler asíncrono para la cascada de estado Employee → User.
 *
 * Recibe DomainEventMessage a través de Messenger async y usa el
 * userId capturado en el evento (no re-resuelve desde Employee).
 *
 * REQ-CST-001:
 *   - Employee desactivado  → User vinculado pasa a INACTIVE
 *   - Employee eliminado    → User vinculado recibe softDelete
 *   - Employee reactivado   → User vinculado pasa a ACTIVE (si estaba INACTIVE)
 */
#[AsMessageHandler]
final readonly class EmployeeStatusCascadeHandler
{
    use DbExceptionHandlerTrait;

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(DomainEventMessage $message): void
    {
        match ($message->eventClass) {
            EmployeeDeactivated::class => $this->handleDeactivated($message),
            EmployeeDeleted::class => $this->handleDeleted($message),
            EmployeeReactivated::class => $this->handleReactivated($message),
            default => null,
        };
    }

    private function handleDeactivated(DomainEventMessage $message): void
    {
        /** @var EmployeeDeactivated $event */
        $event = $message->restoreEvent();

        $user = $this->findUser($event->userId);
        if ($user === null || !$user->isActive()) {
            return;
        }

        $user->deactivate();

        $this->saveUser($user, 'deactivate');
    }

    private function handleDeleted(DomainEventMessage $message): void
    {
        /** @var EmployeeDeleted $event */
        $event = $message->restoreEvent();

        $user = $this->findUser($event->userId);
        if ($user === null || $user->isDeleted()) {
            return;
        }

        $user->softDelete();

        $this->saveUser($user, 'soft-delete');
    }

    private function handleReactivated(DomainEventMessage $message): void
    {
        /** @var EmployeeReactivated $event */
        $event = $message->restoreEvent();

        $user = $this->findUser($event->userId);
        if ($user === null || $user->status() !== UserStatus::INACTIVE) {
            return;
        }

        $user->reactivate();

        $this->saveUser($user, 'reactivate');
    }

    private function findUser(?string $userId): ?\App\Identity\Domain\Entities\User
    {
        if ($userId === null) {
            return null;
        }

        return $this->userRepository->findById(new UserIdValueObject($userId));
    }

    private function saveUser(\App\Identity\Domain\Entities\User $user, string $action): void
    {
        try {
            $this->wrapDbExceptions(function () use ($user): void {
                $this->userRepository->save($user);
            });
        } catch (\Throwable $e) {
            $this->logger->error("Failed to {$action} user cascade: " . $e->getMessage());
        }
    }
}
