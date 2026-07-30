<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\StoreRefreshToken;

use App\Identity\Domain\Entities\RefreshToken;
use App\Identity\Domain\Repositories\RefreshTokenRepositoryInterface;
use App\Identity\Domain\ValueObjects\RefreshTokenIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class StoreRefreshTokenHandler
{
    public function __construct(
        private RefreshTokenRepositoryInterface $refreshTokenRepository,
        private IdGeneratorInterface            $idGenerator,
    ) {
    }

    public function __invoke(StoreRefreshTokenCommand $command): void
    {
        $userId = new UserIdValueObject($command->userId);
        $id = RefreshTokenIdValueObject::fromString($this->idGenerator->generate());

        $token = RefreshToken::create($id, $userId, $command->plainToken);

        $this->refreshTokenRepository->save($token);
    }
}
