<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\ListUsers;

use App\Identity\Application\Query\Response\UserResponse;
use App\Identity\Domain\Repositories\UserRepositoryInterface;

final readonly class ListUsersHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * @return UserResponse[]
     */
    public function __invoke(ListUsersQuery $query): array
    {
        return array_map(
            static fn ($user) => UserResponse::fromEntity($user),
            $this->userRepository->findAll(),
        );
    }
}
