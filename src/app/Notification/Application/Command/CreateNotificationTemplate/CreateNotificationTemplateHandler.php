<?php

declare(strict_types=1);

namespace App\Notification\Application\Command\CreateNotificationTemplate;

use App\Notification\Domain\Entities\NotificationTemplate;
use App\Notification\Domain\Repositories\NotificationTemplateRepositoryInterface;
use App\Notification\Domain\ValueObjects\NotificationTemplateIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;

/**
 * Handler para crear una plantilla de notificación.
 *
 * Es idempotente: si ya existe una plantilla para el mismo EventType + Channel,
 * la omite (no la sobreescribe) y retorna null.
 */
final readonly class CreateNotificationTemplateHandler
{
    public function __construct(
        private NotificationTemplateRepositoryInterface $templateRepository,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    /**
     * @return string|null ULID de la plantilla creada, o null si ya existía
     */
    public function __invoke(CreateNotificationTemplateCommand $command): ?string
    {
        $existing = $this->templateRepository->findByEventTypeAndChannel(
            $command->eventType,
            $command->channel,
        );

        if ($existing !== null) {
            return null;
        }

        $template = NotificationTemplate::create(
            id: new NotificationTemplateIdValueObject($this->idGenerator->generate()),
            eventType: $command->eventType,
            channel: $command->channel,
            subject: $command->subject,
            bodyTemplate: $command->bodyTemplate,
            isActive: $command->isActive,
        );

        $this->templateRepository->save($template);

        return (string) $template->id();
    }
}
