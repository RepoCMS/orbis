<?php

declare(strict_types=1);

namespace App\Notification\Presentation\Http;

use App\Notification\Application\Command\CreateNotificationTemplate\CreateNotificationTemplateCommand;
use App\Notification\Application\Command\CreateNotificationTemplate\CreateNotificationTemplateHandler;
use App\Notification\Application\Command\UpdateNotificationTemplate\UpdateNotificationTemplateCommand;
use App\Notification\Application\Command\UpdateNotificationTemplate\UpdateNotificationTemplateHandler;
use App\Notification\Application\Query\ListNotificationTemplates\ListNotificationTemplatesHandler;
use App\Notification\Application\Query\ListNotificationTemplates\ListNotificationTemplatesQuery;
use App\Notification\Domain\Enums\EventType;
use App\Notification\Domain\Enums\NotificationChannel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/notifications/templates')]
final class ApiNotificationTemplateController extends AbstractController
{
    #[IsGranted('notification.admin')]
    #[Route('', name: 'api_notification_templates_list', methods: ['GET'])]
    public function list(
        Request $request,
        ListNotificationTemplatesHandler $handler,
    ): JsonResponse {
        $query = new ListNotificationTemplatesQuery(
            eventType: $request->query->get('eventType'),
            channel: $request->query->get('channel'),
            isActive: $request->query->get('isActive') !== null ? (bool) $request->query->get('isActive') : null,
        );

        return $this->json(['data' => $handler($query)]);
    }

    #[IsGranted('notification.admin')]
    #[Route('', name: 'api_notification_templates_create', methods: ['POST'])]
    public function create(
        Request $request,
        CreateNotificationTemplateHandler $handler,
    ): JsonResponse {
        try {
            $payload = $request->toArray();
        } catch (\Symfony\Component\HttpFoundation\Exception\JsonException) {
            throw new BadRequestHttpException('JSON inválido');
        }

        $eventType = EventType::tryFrom($payload['eventType'] ?? '');
        $channel   = NotificationChannel::tryFrom($payload['channel'] ?? '');

        if ($eventType === null) {
            return $this->json(['error' => 'eventType inválido'], 422);
        }
        if ($channel === null) {
            return $this->json(['error' => 'channel inválido'], 422);
        }
        if (!isset($payload['bodyTemplate']) || trim((string) $payload['bodyTemplate']) === '') {
            return $this->json(['error' => 'bodyTemplate es obligatorio'], 422);
        }

        $id = $handler(new CreateNotificationTemplateCommand(
            eventType:    $eventType,
            channel:      $channel,
            subject:      $payload['subject'] ?? null,
            bodyTemplate: $payload['bodyTemplate'],
            isActive:     filter_var($payload['isActive'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
        ));

        if ($id === null) {
            return $this->json(
                ['error' => 'Ya existe una plantilla para ese evento y canal.'],
                Response::HTTP_CONFLICT,
            );
        }

        return $this->json(['data' => ['id' => $id]], Response::HTTP_CREATED);
    }

    #[IsGranted('notification.admin')]
    #[Route('/{id}', name: 'api_notification_templates_update', methods: ['PUT'])]
    public function update(
        string $id,
        Request $request,
        UpdateNotificationTemplateHandler $handler,
    ): JsonResponse {
        try {
            $payload = $request->toArray();
        } catch (\Symfony\Component\HttpFoundation\Exception\JsonException) {
            throw new BadRequestHttpException('JSON inválido');
        }

        if (!isset($payload['bodyTemplate']) || trim((string) $payload['bodyTemplate']) === '') {
            return $this->json(['error' => 'bodyTemplate es obligatorio'], 422);
        }

        $handler(new UpdateNotificationTemplateCommand(
            templateId: $id,
            subject: $payload['subject'] ?? null,
            bodyTemplate: $payload['bodyTemplate'],
            isActive: filter_var($payload['isActive'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
        ));

        return $this->json(['data' => ['id' => $id]]);
    }
}
