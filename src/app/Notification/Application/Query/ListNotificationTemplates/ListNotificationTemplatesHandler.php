<?php

declare(strict_types=1);

namespace App\Notification\Application\Query\ListNotificationTemplates;

use App\Notification\Application\Query\Response\NotificationTemplateResponse;
use App\Notification\Domain\Enums\EventType;
use App\Notification\Domain\Enums\NotificationChannel;
use App\Notification\Domain\Repositories\NotificationTemplateRepositoryInterface;

final readonly class ListNotificationTemplatesHandler
{
    public function __construct(
        private NotificationTemplateRepositoryInterface $templateRepository,
    ) {
    }

    public function __invoke(ListNotificationTemplatesQuery $query): array
    {
        $eventType = $query->eventType !== null ? EventType::from($query->eventType) : null;
        $channel = $query->channel !== null ? NotificationChannel::from($query->channel) : null;

        $templates = $this->templateRepository->findAll(
            eventType: $eventType,
            channel: $channel,
            isActive: $query->isActive,
        );

        return array_map(
            static fn ($t) => NotificationTemplateResponse::fromEntity($t),
            $templates,
        );
    }
}
