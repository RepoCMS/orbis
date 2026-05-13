<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\RecordFailedLogin;

use App\Identity\Application\Exception\UserNotFoundException;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;

final readonly class RecordFailedLoginHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(RecordFailedLoginCommand $command): void
    {
        $userId = new UserIdValueObject($command->userId);
        $user = $this->userRepository->findById($userId)
            ?? throw UserNotFoundException::withId($command->userId);

        $user->recordFailedLogin();

        $this->userRepository->save($user);
    }
}
