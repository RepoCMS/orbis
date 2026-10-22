<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\FindUserById;

use App\Identity\Application\Exception\UserNotFoundException;
use App\Identity\Application\Query\Response\UserResponse;
use App\Identity\Domain\Repositories\UserMfaConfigRepositoryInterface;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;

final readonly class FindUserByIdHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserMfaConfigRepositoryInterface $userMfaConfigRepository,
    ) {
    }

    public function __invoke(FindUserByIdQuery $query): UserResponse
    {
        $userId = new UserIdValueObject($query->userId);
        $user = $this->userRepository->findById($userId)
            ?? throw UserNotFoundException::withId($query->userId);

        $mfaConfig = $this->userMfaConfigRepository->findByUserId($userId);

        return UserResponse::fromEntity($user, $mfaConfig);
    }
}
