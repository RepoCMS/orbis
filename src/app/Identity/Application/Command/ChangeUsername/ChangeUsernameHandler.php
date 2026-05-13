<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\ChangeUsername;

use App\Identity\Application\Exception\UsernameAlreadyExistsException;
use App\Identity\Application\Exception\UserNotFoundException;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;

final readonly class ChangeUsernameHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(ChangeUsernameCommand $command): void
    {
        $userId = new UserIdValueObject($command->userId);
        $user = $this->userRepository->findById($userId)
            ?? throw UserNotFoundException::withId($command->userId);

        $newUsername = new UsernameValueObject($command->newUsername);

        $this->ensureUsernameIsUnique($newUsername);

        $user->changeUsername($newUsername);

        $this->userRepository->save($user);
    }

    private function ensureUsernameIsUnique(UsernameValueObject $username): void
    {
        if ($this->userRepository->findByUsername($username) !== null) {
            throw UsernameAlreadyExistsException::withUsername((string) $username);
        }
    }
}
