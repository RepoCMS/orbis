<?php

declare(strict_types=1);

namespace App\Notification\Application\Query\Response;

use App\Notification\Domain\Entities\NotificationTemplate;

final readonly class NotificationTemplateResponse
{
    public function __construct(
        public string $id,
        public string $eventType,
        public string $channel,
        public ?string $subject,
        public string $bodyTemplate,
        public bool $isActive,
    ) {}

    public static function fromEntity(NotificationTemplate $template): self
    {
        return new self(
            id: (string) $template->id(),
            eventType: $template->eventType()->value,
            channel: $template->channel()->value,
            subject: $template->subject(),
            bodyTemplate: $template->bodyTemplate(),
            isActive: $template->isActive(),
        );
    }
}
