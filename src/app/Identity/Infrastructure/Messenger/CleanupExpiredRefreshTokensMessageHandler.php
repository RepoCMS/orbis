<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Messenger;

use App\Identity\Domain\Repositories\RefreshTokenRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CleanupExpiredRefreshTokensMessageHandler
{
    public function __construct(
        private RefreshTokenRepositoryInterface $refreshTokenRepository,
    ) {}

    public function __invoke(CleanupExpiredRefreshTokensMessage $message): void
    {
        $this->refreshTokenRepository->deleteExpired(new DateTimeImmutable());
    }
}
