<?php

declare(strict_types=1);

namespace App\Notification\Presentation\Http;

use App\Notification\Application\Command\UpdateNotificationPreference\UpdateNotificationPreferenceCommand;
use App\Notification\Application\Command\UpdateNotificationPreference\UpdateNotificationPreferenceHandler;
use App\Notification\Application\Query\ListNotificationPreferences\ListNotificationPreferencesHandler;
use App\Notification\Application\Query\ListNotificationPreferences\ListNotificationPreferencesQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/notifications/preferences')]
final class ApiNotificationPreferenceController extends AbstractController
{
    #[IsGranted('notification.admin')]
    #[Route('', name: 'api_notification_preferences_list', methods: ['GET'])]
    public function list(
        Request $request,
        ListNotificationPreferencesHandler $handler,
    ): JsonResponse {
        $recipientType = $request->query->get('recipientType', '');
        $recipientId = $request->query->get('recipientId', '');

        if ($recipientType === '' || $recipientId === '') {
            return $this->json(['error' => 'recipientType y recipientId son requeridos'], 422);
        }

        $query = new ListNotificationPreferencesQuery(
            recipientType: $recipientType,
            recipientId: $recipientId,
        );

        return $this->json(['data' => $handler($query)]);
    }

    #[IsGranted('notification.admin')]
    #[Route('/{id}', name: 'api_notification_preferences_update', methods: ['PATCH'])]
    public function update(
        string $id,
        Request $request,
        UpdateNotificationPreferenceHandler $handler,
    ): JsonResponse {
        try {
            $payload = $request->toArray();
        } catch (\Symfony\Component\HttpFoundation\Exception\JsonException) {
            throw new BadRequestHttpException('JSON inválido');
        }

        if (!isset($payload['isEnabled']) || !is_bool($payload['isEnabled'])) {
            return $this->json(['error' => 'isEnabled es obligatorio y debe ser booleano'], 422);
        }

        $handler(new UpdateNotificationPreferenceCommand(
            preferenceId: $id,
            isEnabled: $payload['isEnabled'],
        ));

        return $this->json(['data' => ['id' => $id]]);
    }
}
