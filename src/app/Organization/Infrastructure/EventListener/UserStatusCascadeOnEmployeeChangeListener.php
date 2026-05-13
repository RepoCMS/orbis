<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\EventListener;

use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Enums\UserStatus;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Organization\Domain\Events\EmployeeDeactivated;
use App\Organization\Domain\Events\EmployeeDeleted;
use App\Organization\Domain\Events\EmployeeReactivated;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Mantiene la consistencia de estado entre Employee y su User vinculado.
 *
 * REQ-CST-001:
 *   - Employee desactivado  → User vinculado pasa a INACTIVE
 *   - Employee eliminado    → User vinculado recibe softDelete
 *   - Employee reactivado   → User vinculado pasa a ACTIVE (si estaba INACTIVE)
 */
final class UserStatusCascadeOnEmployeeChangeListener
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    #[AsEventListener]
    public function onEmployeeDeactivated(EmployeeDeactivated $event): void
    {
        if ($event->userId === null) {
            return;
        }

        $user = $this->userRepository->findById(new UserIdValueObject($event->userId));

        if ($user === null || !$user->isActive()) {
            return;
        }

        $user->deactivate();
        $this->userRepository->save($user);
    }

    #[AsEventListener]
    public function onEmployeeDeleted(EmployeeDeleted $event): void
    {
        if ($event->userId === null) {
            return;
        }

        $user = $this->userRepository->findById(new UserIdValueObject($event->userId));

        if ($user === null || $user->isDeleted()) {
            return;
        }

        $user->softDelete();
        $this->userRepository->save($user);
    }

    #[AsEventListener]
    public function onEmployeeReactivated(EmployeeReactivated $event): void
    {
        if ($event->userId === null) {
            return;
        }

        $user = $this->userRepository->findById(new UserIdValueObject($event->userId));

        if ($user === null || $user->status() !== UserStatus::INACTIVE) {
            return;
        }

        $user->reactivate();
        $this->userRepository->save($user);
    }
}
