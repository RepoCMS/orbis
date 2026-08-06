<?php

declare(strict_types=1);

namespace App\Notification\Application\Query\ListNotificationTemplates;

final readonly class ListNotificationTemplatesQuery
{
    public function __construct(
        public ?string $eventType = null,
        public ?string $channel = null,
        public ?bool $isActive = null,
    ) {
    }
}
