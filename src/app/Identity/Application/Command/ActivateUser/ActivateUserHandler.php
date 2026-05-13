<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\ActivateUser;

use App\Identity\Application\Exception\UserNotFoundException;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;

final readonly class ActivateUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(ActivateUserCommand $command): void
    {
        $userId = new UserIdValueObject($command->userId);
        $user = $this->userRepository->findById($userId)
            ?? throw UserNotFoundException::withId($command->userId);

        $user->activate();

        $this->userRepository->save($user);
    }
}
