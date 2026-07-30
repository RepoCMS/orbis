<?php

declare(strict_types=1);

namespace App\Notification\Presentation\Http;

use App\Notification\Application\Command\UpdateNotificationPreference\UpdateNotificationPreferenceCommand;
use App\Notification\Application\Command\UpdateNotificationPreference\UpdateNotificationPreferenceHandler;
use App\Notification\Application\Query\ListNotificationPreferences\ListNotificationPreferencesHandler;
use App\Notification\Application\Query\ListNotificationPreferences\ListNotificationPreferencesQuery;
use App\Notification\Domain\Enums\EventType;
use App\Notification\Domain\Enums\NotificationChannel;
use App\Notification\Domain\Enums\RecipientType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications/preferences')]
#[IsGranted('notification.admin')]
final class NotificationPreferenceController extends AbstractController
{
    #[Route('', name: 'notification_preference_list', methods: ['GET'])]
    public function list(
        Request $request,
        ListNotificationPreferencesHandler $handler,
    ): Response {
        $recipientType = $request->query->get('recipientType', 'employee');
        $recipientId   = $request->query->get('recipientId', '');

        if ($recipientId === '') {
            return $this->render('notification/preference/list.html.twig', [
                'preferences'    => [],
                'recipientTypes' => RecipientType::cases(),
                'filters'        => [
                    'recipientType' => $recipientType,
                    'recipientId'   => $recipientId,
                ],
                'message' => 'Ingrese un ID de destinatario para ver sus preferencias.',
            ]);
        }

        $preferences = $handler(new ListNotificationPreferencesQuery(
            recipientType: $recipientType,
            recipientId:   $recipientId,
        ));

        return $this->render('notification/preference/list.html.twig', [
            'preferences'    => $preferences,
            'recipientTypes' => RecipientType::cases(),
            'eventTypes'     => EventType::cases(),
            'channels'       => NotificationChannel::cases(),
            'filters'        => [
                'recipientType' => $recipientType,
                'recipientId'   => $recipientId,
            ],
            'message' => null,
        ]);
    }

    #[Route('/{id}/toggle', name: 'notification_preference_toggle', methods: ['POST'])]
    public function toggle(
        string $id,
        Request $request,
        UpdateNotificationPreferenceHandler $handler,
    ): Response {
        $isEnabled = (bool) $request->request->get('isEnabled', false);

        try {
            $handler(new UpdateNotificationPreferenceCommand(
                preferenceId: $id,
                isEnabled:    $isEnabled,
            ));

            $this->addFlash('success', 'Preferencia actualizada.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error al actualizar: ' . $e->getMessage());
        }

        $referer = $request->headers->get('referer', $this->generateUrl('notification_preference_list'));

        return $this->redirect($referer);
    }
}
