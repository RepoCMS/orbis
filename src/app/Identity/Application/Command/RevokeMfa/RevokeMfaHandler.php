<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\RevokeMfa;

use App\Identity\Domain\Entities\AuthLog;
use App\Identity\Domain\Enums\AuthEvent;
use App\Identity\Domain\Repositories\AuthLogRepositoryInterface;
use App\Identity\Domain\Repositories\UserMfaConfigRepositoryInterface;
use App\Identity\Domain\ValueObjects\AuthLogIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class RevokeMfaHandler
{
    public function __construct(
        private UserMfaConfigRepositoryInterface $mfaConfigRepository,
        private AuthLogRepositoryInterface $authLogRepository,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(RevokeMfaCommand $command): void
    {
        $userId = new UserIdValueObject($command->userId);

        $config = $this->mfaConfigRepository->findByUserId($userId);

        if ($config === null) {
            // No MFA config means it's already revoked — idempotent
            return;
        }

        if (!$config->isEnabled()) {
            // Already disabled — idempotent
            return;
        }

        $this->mfaConfigRepository->deleteByUserId($userId);

        $this->authLogRepository->append(
            AuthLog::record(
                AuthLogIdValueObject::fromString($this->idGenerator->generate()),
                $userId,
                AuthEvent::MFA_REVOKED_BY_ADMIN,
                '0.0.0.0',
                'admin',
            ),
        );
    }
}
