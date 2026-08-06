<?php

declare(strict_types=1);

namespace App\Notification\Application\Query\ListNotificationPreferences;

final readonly class ListNotificationPreferencesQuery
{
    public function __construct(
        public string $recipientType,
        public string $recipientId,
    ) {
    }
}
