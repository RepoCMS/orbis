<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Template;

use App\Notification\Application\Service\TemplateVariableResolver;
use App\Notification\Domain\Enums\EventType;
use App\Notification\Domain\Enums\NotificationChannel;
use App\Notification\Domain\Repositories\NotificationTemplateRepositoryInterface;

/**
 * Renderiza plantillas de notificación.
 *
 * Dado un EventType y NotificationChannel, busca la plantilla activa
 * y la renderiza con las variables proporcionadas, retornando subject y body.
 *
 * Si no hay plantilla activa para el par (eventType, channel), retorna null
 * (el flujo caller decide si omite silenciosamente la notificación).
 */
final readonly class NotificationTemplateRenderer
{
    public function __construct(
        private NotificationTemplateRepositoryInterface $templateRepository,
        private TemplateVariableResolver $variableResolver,
    ) {
    }

    /**
     * Renderiza una plantilla para un evento y canal dados.
     *
     * @param EventType          $eventType     Tipo de evento
     * @param NotificationChannel $channel       Canal de notificación
     * @param string             $recipientType employee|contact|user|location
     * @param string             $recipientId   ULID del destinatario
     * @param array              $templateVars  Variables adicionales para el template
     *
     * @return array{subject: ?string, body: string}|null Plantilla renderizada o null si no hay activa
     */
    public function render(
        EventType $eventType,
        NotificationChannel $channel,
        string $recipientType,
        string $recipientId,
        array $templateVars = [],
    ): ?array {
        $template = $this->templateRepository->findByEventTypeAndChannel($eventType, $channel);

        if ($template === null || !$template->isActive()) {
            return null;
        }

        $body = $this->variableResolver->resolve(
            $template->bodyTemplate(),
            $recipientType,
            $recipientId,
            $templateVars,
        );

        $subject = $template->subject();

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }

    /**
     * Renderiza con un subject por defecto si la plantilla no tiene subject propio.
     *
     * @param EventType          $eventType     Tipo de evento
     * @param NotificationChannel $channel       Canal de notificación
     * @param string             $recipientType employee|contact|user|location
     * @param string             $recipientId   ULID del destinatario
     * @param string             $defaultSubject Subject por defecto si la plantilla no tiene uno
     * @param array              $templateVars  Variables adicionales para el template
     *
     * @return array{subject: ?string, body: string}|null Plantilla renderizada o null si no hay activa
     */
    public function renderWithDefaultSubject(
        EventType $eventType,
        NotificationChannel $channel,
        string $recipientType,
        string $recipientId,
        string $defaultSubject,
        array $templateVars = [],
    ): ?array {
        $result = $this->render($eventType, $channel, $recipientType, $recipientId, $templateVars);

        if ($result === null) {
            return null;
        }

        if ($result['subject'] === null || $result['subject'] === '') {
            $result['subject'] = $defaultSubject;
        }

        return $result;
    }
}
