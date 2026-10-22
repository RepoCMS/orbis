<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\ListUsers;

use App\Identity\Application\Query\Response\UserResponse;
use App\Identity\Domain\Repositories\UserMfaConfigRepositoryInterface;
use App\Identity\Domain\Repositories\UserRepositoryInterface;

final readonly class ListUsersHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserMfaConfigRepositoryInterface $userMfaConfigRepository,
    ) {
    }

    /**
     * @return UserResponse[]
     */
    public function __invoke(ListUsersQuery $query): array
    {
        $users = $this->userRepository->findAll();
        $mfaConfigs = [];
        foreach ($users as $user) {
            $mfaConfigs[(string) $user->id()] = $this->userMfaConfigRepository->findByUserId($user->id());
        }

        return array_map(
            static fn ($user) => UserResponse::fromEntity($user, $mfaConfigs[(string) $user->id()] ?? null),
            $users,
        );
    }
}
