<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\RevokeRefreshToken;

use App\Identity\Domain\Repositories\RefreshTokenRepositoryInterface;

final readonly class RevokeRefreshTokenHandler
{
    public function __construct(
        private RefreshTokenRepositoryInterface $refreshTokenRepository,
    ) {
    }

    public function __invoke(RevokeRefreshTokenCommand $command): void
    {
        $token = $this->refreshTokenRepository->findByHash($command->tokenHash);

        if ($token === null) {
            // Already revoked or non-existent — idempotent
            return;
        }

        $token->revoke();

        $this->refreshTokenRepository->save($token);
    }
}
