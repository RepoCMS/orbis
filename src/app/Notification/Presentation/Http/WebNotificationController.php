<?php

declare(strict_types=1);

namespace App\Notification\Presentation\Http;

use App\Notification\Application\Command\MarkAllNotificationsRead\MarkAllNotificationsReadCommand;
use App\Notification\Application\Command\MarkAllNotificationsRead\MarkAllNotificationsReadHandler;
use App\Notification\Application\Command\MarkNotificationRead\MarkNotificationReadCommand;
use App\Notification\Application\Command\MarkNotificationRead\MarkNotificationReadHandler;
use App\Notification\Application\Command\RetryNotification\RetryNotificationCommand;
use App\Notification\Application\Command\RetryNotification\RetryNotificationHandler;
use App\Notification\Application\Command\UpdateNotificationTemplate\UpdateNotificationTemplateCommand;
use App\Notification\Application\Command\UpdateNotificationTemplate\UpdateNotificationTemplateHandler;
use App\Notification\Application\Exception\NotificationAccessDeniedException;
use App\Notification\Application\Exception\NotificationLogNotFoundException;
use App\Notification\Application\Exception\NotificationTemplateNotFoundException;
use App\Notification\Application\Query\CountUnreadNotifications\CountUnreadNotificationsHandler;
use App\Notification\Application\Query\CountUnreadNotifications\CountUnreadNotificationsQuery;
use App\Notification\Application\Query\ListInboxNotifications\ListInboxNotificationsHandler;
use App\Notification\Application\Query\ListInboxNotifications\ListInboxNotificationsQuery;
use App\Notification\Application\Query\ListNotificationLogs\ListNotificationLogsHandler;
use App\Notification\Application\Query\ListNotificationLogs\ListNotificationLogsQuery;
use App\Notification\Application\Query\ListNotificationTemplates\ListNotificationTemplatesHandler;
use App\Notification\Application\Query\ListNotificationTemplates\ListNotificationTemplatesQuery;
use App\Notification\Application\Service\SecurityUserResolverInterface;
use App\Notification\Domain\Enums\EventType;
use App\Notification\Domain\Enums\NotificationChannel;
use App\Notification\Domain\Enums\NotificationStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications')]
final class WebNotificationController extends AbstractController
{
    /**
     * Inbox de notificaciones IN_APP del empleado autenticado.
     */
    #[IsGranted('IS_AUTHENTICATED')]
    #[Route('/inbox', name: 'notification_inbox', methods: ['GET'])]
    public function inbox(
        ListInboxNotificationsHandler $listHandler,
        CountUnreadNotificationsHandler $countHandler,
        SecurityUserResolverInterface $userResolver,
    ): Response {
        $employeeId = $userResolver->getUserId();
        if ($employeeId === null) {
            throw $this->createAccessDeniedException('Usuario no autenticado.');
        }

        $result = $listHandler(new ListInboxNotificationsQuery(
            employeeId: $employeeId,
            unreadOnly: false,
            page: 1,
            limit: 50,
        ));

        $unreadCount = $countHandler(new CountUnreadNotificationsQuery(
            employeeId: $employeeId,
        ));

        return $this->render('notification/inbox.html.twig', [
            'notifications' => $result['data'],
            'unreadCount' => $result['unreadCount'],
            'totalCount' => count($result['data']),
        ]);
    }

    /**
     * Marca una notificación como leída (Turbo Frame).
     */
    #[IsGranted('IS_AUTHENTICATED')]
    #[Route('/inbox/{id}/read', name: 'notification_inbox_mark_read', methods: ['POST'])]
    public function markRead(
        string $id,
        MarkNotificationReadHandler $handler,
        SecurityUserResolverInterface $userResolver,
    ): Response {
        $employeeId = $userResolver->getUserId();
        if ($employeeId === null) {
            throw $this->createAccessDeniedException();
        }

        try {
            $handler(new MarkNotificationReadCommand(
                logId: $id,
                employeeId: $employeeId,
            ));
            $this->addFlash('success', 'Notificación marcada como leída.');
        } catch (NotificationLogNotFoundException) {
            $this->addFlash('error', 'La notificación no existe.');
        } catch (NotificationAccessDeniedException) {
            $this->addFlash('error', 'No tienes permiso para modificar esta notificación.');
        }

        // Redirect back to inbox (Turbo will follow)
        return $this->redirectToRoute('notification_inbox');
    }

    /**
     * Marca todas las notificaciones como leídas (Turbo Stream).
     */
    #[IsGranted('IS_AUTHENTICATED')]
    #[Route('/inbox/read-all', name: 'notification_inbox_mark_all_read', methods: ['POST'])]
    public function markAllRead(
        MarkAllNotificationsReadHandler $handler,
        SecurityUserResolverInterface $userResolver,
    ): Response {
        $employeeId = $userResolver->getUserId();
        if ($employeeId === null) {
            throw $this->createAccessDeniedException();
        }

        $handler(new MarkAllNotificationsReadCommand(
            employeeId: $employeeId,
        ));

        $this->addFlash('success', 'Notificaciones marcadas como leídas.');

        return $this->redirectToRoute('notification_inbox');
    }

    /**
     * Badge de conteo de no leídas para el navbar (lazy Turbo Frame).
     */
    #[IsGranted('IS_AUTHENTICATED')]
    #[Route('/badge', name: 'notification_badge', methods: ['GET'])]
    public function badge(
        CountUnreadNotificationsHandler $handler,
        SecurityUserResolverInterface $userResolver,
    ): Response {
        $employeeId = $userResolver->getUserId();

        if ($employeeId === null) {
            return $this->render('notification/_badge.html.twig', ['unreadCount' => 0]);
        }

        $unreadCount = $handler(new CountUnreadNotificationsQuery(
            employeeId: $employeeId,
        ));

        return $this->render('notification/_badge.html.twig', [
            'unreadCount' => $unreadCount,
        ]);
    }

    /**
     * Panel de administración de notificaciones.
     */
    #[IsGranted('notification.admin')]
    #[Route('/admin', name: 'notification_admin', methods: ['GET'])]
    public function admin(
        Request $request,
        ListNotificationTemplatesHandler $templatesHandler,
        ListNotificationLogsHandler $logsHandler,
    ): Response {
        $tab = $request->query->get('tab', 'templates');

        // ── Templates ──
        $templates = $templatesHandler(new ListNotificationTemplatesQuery(
            eventType: $request->query->get('templateEventType'),
            channel: $request->query->get('templateChannel'),
        ));

        // ── Logs ──
        $logs = $logsHandler(new ListNotificationLogsQuery(
            status: $request->query->get('logStatus'),
            recipientType: $request->query->get('logRecipientType'),
            recipientId: $request->query->get('logRecipientId'),
            eventType: $request->query->get('logEventType'),
            dateFrom: $request->query->get('logDateFrom'),
            dateTo: $request->query->get('logDateTo'),
            page: (int) $request->query->get('page', 1),
            limit: 50,
        ));

        return $this->render('notification/admin.html.twig', [
            'tab' => $tab,
            'templates' => $templates,
            'logs' => $logs,
            'eventTypes' => EventType::cases(),
            'channels' => NotificationChannel::cases(),
            'statuses' => NotificationStatus::cases(),
            'filters' => [
                'templateEventType' => $request->query->get('templateEventType'),
                'templateChannel' => $request->query->get('templateChannel'),
                'logStatus' => $request->query->get('logStatus'),
                'logRecipientType' => $request->query->get('logRecipientType'),
                'logRecipientId' => $request->query->get('logRecipientId'),
                'logEventType' => $request->query->get('logEventType'),
                'logDateFrom' => $request->query->get('logDateFrom'),
                'logDateTo' => $request->query->get('logDateTo'),
            ],
        ]);
    }

    /**
     * Actualiza un template de notificación.
     */
    #[IsGranted('notification.admin')]
    #[Route('/admin/templates/{id}/update', name: 'notification_admin_template_update', methods: ['POST'])]
    public function updateTemplate(
        string $id,
        Request $request,
        UpdateNotificationTemplateHandler $handler,
    ): Response {
        try {
            $handler(new UpdateNotificationTemplateCommand(
                templateId: $id,
                subject: $request->request->get('subject') ?: null,
                bodyTemplate: (string) $request->request->get('bodyTemplate', ''),
                isActive: (bool) $request->request->get('isActive', false),
            ));

            $this->addFlash('success', 'Template actualizado correctamente.');
        } catch (NotificationTemplateNotFoundException) {
            $this->addFlash('error', 'El template no existe.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('notification_admin', ['tab' => 'templates']);
    }

    /**
     * Reintenta una notificación fallida.
     */
    #[IsGranted('notification.admin')]
    #[Route('/admin/logs/{id}/retry', name: 'notification_admin_log_retry', methods: ['POST'])]
    public function retryLog(
        string $id,
        RetryNotificationHandler $handler,
    ): Response {
        try {
            $handler(new RetryNotificationCommand(logId: $id));
            $this->addFlash('success', 'Notificación re-enviada para reintento.');
        } catch (NotificationLogNotFoundException) {
            $this->addFlash('error', 'El log de notificación no existe.');
        } catch (\App\Notification\Application\Exception\InvalidNotificationStatusException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('notification_admin', ['tab' => 'logs']);
    }
}
