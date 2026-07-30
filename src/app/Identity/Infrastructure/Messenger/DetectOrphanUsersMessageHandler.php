<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Messenger;

use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\Service\OrphanUserCheckerInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class DetectOrphanUsersMessageHandler
{
    public function __construct(
        private OrphanUserCheckerInterface $orphanUserChecker,
        private UserRepositoryInterface $userRepository,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(DetectOrphanUsersMessage $message): void
    {
        $allUsers = $this->userRepository->findAll();
        $orphanCount = 0;

        foreach ($allUsers as $user) {
            if ($user->isDeleted()) {
                continue;
            }

            $userId = new UserIdValueObject((string) $user->id());
            if (!$this->orphanUserChecker->isBound($userId)) {
                $orphanCount++;
                $this->logger->warning('Orphan user detected (scheduled check): {username} ({id})', [
                    'username' => (string) $user->username(),
                    'id' => (string) $user->id(),
                ]);
            }
        }

        $this->logger->info('Scheduled orphan user detection completed. Found {count} orphan(s).', [
            'count' => $orphanCount,
        ]);
    }
}
