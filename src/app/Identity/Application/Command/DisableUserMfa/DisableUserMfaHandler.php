<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\DisableUserMfa;

use App\Identity\Domain\Repositories\UserMfaConfigRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;

final readonly class DisableUserMfaHandler
{
    public function __construct(
        private UserMfaConfigRepositoryInterface $mfaConfigRepository,
    ) {
    }

    public function __invoke(DisableUserMfaCommand $command): void
    {
        $userId = new UserIdValueObject($command->userId);

        $config = $this->mfaConfigRepository->findByUserId($userId);

        if ($config === null) {
            // No MFA config means it's already disabled — idempotent
            return;
        }

        if (!$config->isEnabled()) {
            // Already disabled — idempotent
            return;
        }

        $this->mfaConfigRepository->deleteByUserId($userId);
    }
}
