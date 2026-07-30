<?php

declare(strict_types=1);

namespace App\Notification\Presentation\Http;

use App\Notification\Application\Command\MarkAllNotificationsRead\MarkAllNotificationsReadCommand;
use App\Notification\Application\Command\MarkAllNotificationsRead\MarkAllNotificationsReadHandler;
use App\Notification\Application\Command\MarkNotificationRead\MarkNotificationReadCommand;
use App\Notification\Application\Command\MarkNotificationRead\MarkNotificationReadHandler;
use App\Notification\Application\Query\CountUnreadNotifications\CountUnreadNotificationsHandler;
use App\Notification\Application\Query\CountUnreadNotifications\CountUnreadNotificationsQuery;
use App\Notification\Application\Query\ListInboxNotifications\ListInboxNotificationsHandler;
use App\Notification\Application\Query\ListInboxNotifications\ListInboxNotificationsQuery;
use App\Notification\Application\Service\SecurityUserResolverInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/notifications/inbox')]
final class ApiNotificationInboxController extends AbstractController
{
    #[IsGranted('IS_AUTHENTICATED')]
    #[Route('', name: 'api_notification_inbox_list', methods: ['GET'])]
    public function list(
        Request $request,
        ListInboxNotificationsHandler $handler,
        SecurityUserResolverInterface $userResolver,
    ): JsonResponse {
        $employeeId = $userResolver->getUserId();
        if ($employeeId === null) {
            return $this->json(['error' => 'No autenticado'], 401);
        }

        $query = new ListInboxNotificationsQuery(
            employeeId: $employeeId,
            unreadOnly: (bool) $request->query->get('unreadOnly', false),
            page: (int) $request->query->get('page', 1),
            limit: (int) $request->query->get('limit', 20),
        );

        return $this->json($handler($query));
    }

    #[IsGranted('IS_AUTHENTICATED')]
    #[Route('/unread-count', name: 'api_notification_inbox_unread_count', methods: ['GET'])]
    public function unreadCount(
        CountUnreadNotificationsHandler $handler,
        SecurityUserResolverInterface $userResolver,
    ): JsonResponse {
        $employeeId = $userResolver->getUserId();
        if ($employeeId === null) {
            return $this->json(['error' => 'No autenticado'], 401);
        }
        $count = $handler(new CountUnreadNotificationsQuery(employeeId: $employeeId));

        return $this->json(['data' => ['unreadCount' => $count]]);
    }

    #[IsGranted('IS_AUTHENTICATED')]
    #[Route('/{id}/read', name: 'api_notification_inbox_mark_read', methods: ['POST'])]
    public function markRead(
        string $id,
        MarkNotificationReadHandler $handler,
        SecurityUserResolverInterface $userResolver,
    ): JsonResponse {
        $employeeId = $userResolver->getUserId();
        if ($employeeId === null) {
            return $this->json(['error' => 'No autenticado'], 401);
        }

        $handler(new MarkNotificationReadCommand(
            logId: $id,
            employeeId: $employeeId,
        ));

        return $this->json(['data' => ['id' => $id]]);
    }

    #[IsGranted('IS_AUTHENTICATED')]
    #[Route('/read-all', name: 'api_notification_inbox_mark_all_read', methods: ['POST'])]
    public function markAllRead(
        MarkAllNotificationsReadHandler $handler,
        SecurityUserResolverInterface $userResolver,
    ): JsonResponse {
        $employeeId = $userResolver->getUserId();
        if ($employeeId === null) {
            return $this->json(['error' => 'No autenticado'], 401);
        }
        $handler(new MarkAllNotificationsReadCommand(employeeId: $employeeId));

        return $this->json(['data' => ['id' => $employeeId]]);
    }
}
