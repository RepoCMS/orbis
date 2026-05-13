<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\FindUserByUsername;

use App\Identity\Application\Exception\UserNotFoundException;
use App\Identity\Application\Query\Response\UserResponse;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\UsernameValueObject;

final readonly class FindUserByUsernameHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(FindUserByUsernameQuery $query): UserResponse
    {
        $username = new UsernameValueObject($query->username);
        $user = $this->userRepository->findByUsername($username)
            ?? throw UserNotFoundException::withId($query->username);

        return UserResponse::fromEntity($user);
    }
}
