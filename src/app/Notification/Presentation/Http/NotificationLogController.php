<?php

declare(strict_types=1);

namespace App\Notification\Presentation\Http;

use App\Notification\Application\Command\RetryNotification\RetryNotificationCommand;
use App\Notification\Application\Command\RetryNotification\RetryNotificationHandler;
use App\Notification\Application\Exception\InvalidNotificationStatusException;
use App\Notification\Application\Exception\NotificationLogNotFoundException;
use App\Notification\Application\Query\ListNotificationLogs\ListNotificationLogsHandler;
use App\Notification\Application\Query\ListNotificationLogs\ListNotificationLogsQuery;
use App\Notification\Domain\Enums\EventType;
use App\Notification\Domain\Enums\NotificationChannel;
use App\Notification\Domain\Enums\NotificationStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications/logs')]
#[IsGranted('notification.admin')]
final class NotificationLogController extends AbstractController
{
    #[Route('', name: 'notification_log_list', methods: ['GET'])]
    public function list(
        Request $request,
        ListNotificationLogsHandler $handler,
    ): Response {
        $page  = (int) $request->query->get('page', 1);
        $limit = 50;

        $logs = $handler(new ListNotificationLogsQuery(
            status:        $request->query->get('status'),
            recipientType: $request->query->get('recipientType'),
            recipientId:   $request->query->get('recipientId'),
            eventType:     $request->query->get('eventType'),
            dateFrom:      $request->query->get('dateFrom'),
            dateTo:        $request->query->get('dateTo'),
            page:          $page,
            limit:         $limit,
        ));

        return $this->render('notification/log/list.html.twig', [
            'logs'       => $logs,
            'statuses'   => NotificationStatus::cases(),
            'eventTypes' => EventType::cases(),
            'channels'   => NotificationChannel::cases(),
            'filters'    => [
                'status'        => $request->query->get('status'),
                'recipientType' => $request->query->get('recipientType'),
                'recipientId'   => $request->query->get('recipientId'),
                'eventType'     => $request->query->get('eventType'),
                'dateFrom'      => $request->query->get('dateFrom'),
                'dateTo'        => $request->query->get('dateTo'),
            ],
            'page' => $page,
        ]);
    }

    #[Route('/{id}', name: 'notification_log_show', methods: ['GET'])]
    public function show(
        string $id,
        ListNotificationLogsHandler $handler,
    ): Response {
        $logs = $handler(new ListNotificationLogsQuery(
            limit: 100,
        ));

        $log = null;
        foreach ($logs as $l) {
            if ($l->id === $id) {
                $log = $l;
                break;
            }
        }

        if ($log === null) {
            throw $this->createNotFoundException('Registro de notificación no encontrado.');
        }

        return $this->render('notification/log/show.html.twig', [
            'log'        => $log,
            'statuses'   => NotificationStatus::cases(),
            'eventTypes' => EventType::cases(),
            'channels'   => NotificationChannel::cases(),
        ]);
    }

    #[Route('/{id}/retry', name: 'notification_log_retry', methods: ['POST'])]
    public function retry(
        string $id,
        RetryNotificationHandler $handler,
    ): Response {
        try {
            $handler(new RetryNotificationCommand(logId: $id));
            $this->addFlash('success', 'Notificación re-enviada para reintento.');
        } catch (NotificationLogNotFoundException) {
            $this->addFlash('error', 'El registro de notificación no existe.');
        } catch (InvalidNotificationStatusException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('notification_log_list');
    }
}
