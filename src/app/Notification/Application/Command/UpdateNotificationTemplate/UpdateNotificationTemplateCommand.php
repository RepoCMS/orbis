<?php

declare(strict_types=1);

namespace App\Notification\Application\Command\UpdateNotificationTemplate;

final readonly class UpdateNotificationTemplateCommand
{
    public function __construct(
        public string $templateId,
        public ?string $subject,
        public string $bodyTemplate,
        public bool $isActive,
    ) {
    }
}
