<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\ChangePassword;

use App\Identity\Application\Exception\UserNotFoundException;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\Service\PasswordHasherInterface;
use App\Identity\Domain\ValueObjects\RawPasswordValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;

final readonly class ChangePasswordHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(ChangePasswordCommand $command): void
    {
        $userId = new UserIdValueObject($command->userId);
        $user = $this->userRepository->findById($userId)
            ?? throw UserNotFoundException::withId($command->userId);

        $rawPassword = new RawPasswordValueObject($command->newRawPassword);
        $hashedPassword = $this->passwordHasher->hash($rawPassword);

        $user->changePassword($hashedPassword);

        $this->userRepository->save($user);
    }
}
