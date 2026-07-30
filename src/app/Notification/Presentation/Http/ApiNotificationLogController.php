<?php

declare(strict_types=1);

namespace App\Notification\Presentation\Http;

use App\Notification\Application\Command\RetryNotification\RetryNotificationCommand;
use App\Notification\Application\Command\RetryNotification\RetryNotificationHandler;
use App\Notification\Application\Exception\InvalidNotificationStatusException;
use App\Notification\Application\Exception\NotificationLogNotFoundException;
use App\Notification\Application\Query\ListNotificationLogs\ListNotificationLogsHandler;
use App\Notification\Application\Query\ListNotificationLogs\ListNotificationLogsQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/notifications/logs')]
final class ApiNotificationLogController extends AbstractController
{
    #[IsGranted('notification.admin')]
    #[Route('', name: 'api_notification_logs_list', methods: ['GET'])]
    public function list(
        Request $request,
        ListNotificationLogsHandler $handler,
    ): JsonResponse {
        $query = new ListNotificationLogsQuery(
            status: $request->query->get('status'),
            recipientType: $request->query->get('recipientType'),
            recipientId: $request->query->get('recipientId'),
            eventType: $request->query->get('eventType'),
            dateFrom: $request->query->get('dateFrom'),
            dateTo: $request->query->get('dateTo'),
            page: (int) $request->query->get('page', 1),
            limit: (int) $request->query->get('limit', 20),
        );

        return $this->json(['data' => $handler($query)]);
    }

    #[IsGranted('notification.admin')]
    #[Route('/{id}/retry', name: 'api_notification_logs_retry', methods: ['POST'])]
    public function retry(
        string $id,
        RetryNotificationHandler $handler,
    ): JsonResponse {
        try {
            $handler(new RetryNotificationCommand(logId: $id));
        } catch (NotificationLogNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        } catch (InvalidNotificationStatusException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json(['data' => ['id' => $id]]);
    }
}
