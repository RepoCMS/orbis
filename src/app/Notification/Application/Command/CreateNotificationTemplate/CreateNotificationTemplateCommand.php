<?php

declare(strict_types=1);

namespace App\Notification\Application\Command\CreateNotificationTemplate;

use App\Notification\Domain\Enums\EventType;
use App\Notification\Domain\Enums\NotificationChannel;

/**
 * Command para crear una plantilla de notificación reutilizable.
 *
 * Este Command puede ser usado desde consola (seed), importers,
 * o desde la UI de administración.
 */
final readonly class CreateNotificationTemplateCommand
{
    public function __construct(
        public EventType $eventType,
        public NotificationChannel $channel,
        public ?string $subject,
        public string $bodyTemplate,
        public bool $isActive = true,
    ) {
    }
}
