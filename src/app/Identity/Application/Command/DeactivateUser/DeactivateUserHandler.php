<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\DeactivateUser;

use App\Identity\Application\Exception\UserNotFoundException;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;

final readonly class DeactivateUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(DeactivateUserCommand $command): void
    {
        $userId = new UserIdValueObject($command->userId);
        $user = $this->userRepository->findById($userId)
            ?? throw UserNotFoundException::withId($command->userId);

        $user->deactivate();

        $this->userRepository->save($user);
    }
}
