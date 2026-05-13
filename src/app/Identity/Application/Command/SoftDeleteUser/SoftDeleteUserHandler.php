<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\SoftDeleteUser;

use App\Identity\Application\Exception\UserNotFoundException;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;

final readonly class SoftDeleteUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(SoftDeleteUserCommand $command): void
    {
        $userId = new UserIdValueObject($command->userId);
        $user = $this->userRepository->findById($userId)
            ?? throw UserNotFoundException::withId($command->userId);

        $user->softDelete();

        $this->userRepository->save($user);
    }
}
