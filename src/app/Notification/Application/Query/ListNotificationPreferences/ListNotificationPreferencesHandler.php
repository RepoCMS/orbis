<?php

declare(strict_types=1);

namespace App\Notification\Application\Query\ListNotificationPreferences;

use App\Notification\Application\Query\Response\NotificationPreferenceResponse;
use App\Notification\Domain\Enums\RecipientType;
use App\Notification\Domain\Repositories\NotificationPreferenceRepositoryInterface;

final readonly class ListNotificationPreferencesHandler
{
    public function __construct(
        private NotificationPreferenceRepositoryInterface $preferenceRepository,
    ) {
    }

    public function __invoke(ListNotificationPreferencesQuery $query): array
    {
        $preferences = $this->preferenceRepository->findByRecipient(
            RecipientType::from($query->recipientType),
            $query->recipientId,
        );

        return array_map(
            static fn ($p) => NotificationPreferenceResponse::fromEntity($p),
            $preferences,
        );
    }
}
