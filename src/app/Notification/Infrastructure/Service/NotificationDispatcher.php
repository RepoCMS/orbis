<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Service;

use App\Notification\Application\Command\SendNotification\SendNotificationCommand;
use App\Notification\Application\Service\TemplateVariableResolver;
use App\Notification\Domain\Enums\EventType;
use App\Notification\Domain\Enums\NotificationChannel;
use App\Notification\Domain\Enums\RecipientType;
use App\Notification\Domain\Repositories\NotificationPreferenceRepositoryInterface;
use App\Notification\Domain\Repositories\NotificationTemplateRepositoryInterface;
use App\Notification\Domain\Services\ContactResolverInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class NotificationDispatcher
{
    private const SECURITY_EVENTS = [
        EventType::USER_ACCOUNT_ACTIVATED,
        EventType::USER_PASSWORD_RESET,
    ];

    public function __construct(
        private NotificationTemplateRepositoryInterface $templateRepository,
        private NotificationPreferenceRepositoryInterface $preferenceRepository,
        private ContactResolverInterface $contactResolver,
        private TemplateVariableResolver $templateResolver,
        private MessageBusInterface $messageBus,
    ) {}

    public function dispatchForEvent(
        EventType $eventType,
        array $recipients,
        string $subject,
        string $bodyTemplate,
        array $templateVars = [],
        ?string $referenceType = null,
        ?string $referenceId = null,
    ): void {
        foreach ($recipients as $recipient) {
            $this->dispatchForRecipient(
                eventType: $eventType,
                recipientType: $recipient['type'],
                recipientId: $recipient['id'],
                subject: $subject,
                bodyTemplate: $bodyTemplate,
                templateVars: $templateVars,
                referenceType: $referenceType,
                referenceId: $referenceId,
            );
        }
    }

    private function dispatchForRecipient(
        EventType $eventType,
        RecipientType $recipientType,
        string $recipientId,
        string $subject,
        string $bodyTemplate,
        array $templateVars = [],
        ?string $referenceType = null,
        ?string $referenceId = null,
    ): void {
        $isSecurity = $this->isSecurityEvent($eventType);
        $channels = $isSecurity
            ? [NotificationChannel::EMAIL]
            : [NotificationChannel::IN_APP, NotificationChannel::EMAIL, NotificationChannel::WHATSAPP];

        foreach ($channels as $channel) {
            $template = $this->templateRepository->findByEventTypeAndChannel($eventType, $channel);

            if ($template === null) {
                continue;
            }

            if (!$isSecurity) {
                $preferences = $this->preferenceRepository->findByRecipient($recipientType, $recipientId);
                $prefMatch = array_filter(
                    $preferences,
                    static fn ($p) => $p->channel() === $channel && $p->eventType() === $eventType,
                );

                $preference = !empty($prefMatch) ? reset($prefMatch) : null;

                if ($preference !== null && !$preference->isEnabled()) {
                    continue;
                }
            }

            if ($channel !== NotificationChannel::IN_APP) {
                $contact = $this->contactResolver->resolve($recipientType->value, $recipientId);

                if ($channel === NotificationChannel::EMAIL && empty($contact['email'])) {
                    continue;
                }

                if ($channel === NotificationChannel::WHATSAPP && empty($contact['phone'])) {
                    continue;
                }
            }

            $renderedBody = $this->templateResolver->resolve(
                $template->bodyTemplate(),
                $recipientType->value,
                $recipientId,
                $templateVars,
            );

            $renderedSubject = $template->subject() ?? $subject;

            $this->messageBus->dispatch(new SendNotificationCommand(
                recipientType: $recipientType->value,
                recipientId: $recipientId,
                channel: $channel->value,
                eventType: $eventType->value,
                subject: $renderedSubject,
                body: $renderedBody,
                referenceType: $referenceType,
                referenceId: $referenceId,
            ));
        }
    }

    private function isSecurityEvent(EventType $eventType): bool
    {
        return in_array($eventType, self::SECURITY_EVENTS, true);
    }
}
