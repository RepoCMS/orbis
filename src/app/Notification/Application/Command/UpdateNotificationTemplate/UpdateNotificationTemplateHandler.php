<?php

declare(strict_types=1);

namespace App\Notification\Application\Command\UpdateNotificationTemplate;

use App\Notification\Application\Exception\NotificationTemplateNotFoundException;
use App\Notification\Domain\Repositories\NotificationTemplateRepositoryInterface;
use App\Notification\Domain\ValueObjects\NotificationTemplateIdValueObject;

final readonly class UpdateNotificationTemplateHandler
{
    public function __construct(
        private NotificationTemplateRepositoryInterface $templateRepository,
    ) {}

    public function __invoke(UpdateNotificationTemplateCommand $command): void
    {
        $id = NotificationTemplateIdValueObject::fromString($command->templateId);

        $template = $this->templateRepository->findById($id);

        if ($template === null) {
            throw NotificationTemplateNotFoundException::withId($command->templateId);
        }

        $template->update($command->subject, $command->bodyTemplate, $command->isActive);

        $this->templateRepository->save($template);
    }
}
