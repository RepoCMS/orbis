<?php

declare(strict_types=1);

namespace App\Notification\Presentation\Http;

use App\Notification\Application\Command\CreateNotificationTemplate\CreateNotificationTemplateCommand;
use App\Notification\Application\Command\CreateNotificationTemplate\CreateNotificationTemplateHandler;
use App\Notification\Application\Command\UpdateNotificationTemplate\UpdateNotificationTemplateCommand;
use App\Notification\Application\Command\UpdateNotificationTemplate\UpdateNotificationTemplateHandler;
use App\Notification\Application\Exception\NotificationTemplateNotFoundException;
use App\Notification\Application\Query\ListNotificationTemplates\ListNotificationTemplatesHandler;
use App\Notification\Application\Query\ListNotificationTemplates\ListNotificationTemplatesQuery;
use App\Notification\Domain\Enums\EventType;
use App\Notification\Domain\Enums\NotificationChannel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications/templates')]
#[IsGranted('notification.admin')]
final class NotificationTemplateController extends AbstractController
{
    #[Route('', name: 'notification_template_list', methods: ['GET'])]
    public function list(
        Request $request,
        ListNotificationTemplatesHandler $handler,
    ): Response {
        $templates = $handler(new ListNotificationTemplatesQuery(
            eventType: $request->query->get('eventType'),
            channel: $request->query->get('channel'),
            isActive: $request->query->get('isActive') !== null ? (bool) $request->query->get('isActive') : null,
        ));

        return $this->render('notification/template/list.html.twig', [
            'templates'  => $templates,
            'eventTypes' => EventType::cases(),
            'channels'   => NotificationChannel::cases(),
            'filters'    => [
                'eventType' => $request->query->get('eventType'),
                'channel'   => $request->query->get('channel'),
                'isActive'  => $request->query->get('isActive'),
            ],
        ]);
    }

    #[Route('/new', name: 'notification_template_new', methods: ['GET'])]
    public function newForm(): Response
    {
        return $this->render('notification/template/form.html.twig', [
            'template'   => null,
            'eventTypes' => EventType::cases(),
            'channels'   => NotificationChannel::cases(),
            'errors'     => [],
        ]);
    }

    #[Route('/new', name: 'notification_template_create', methods: ['POST'])]
    public function create(
        Request $request,
        CreateNotificationTemplateHandler $handler,
    ): Response {
        $eventTypeValue = trim((string) $request->request->get('eventType', ''));
        $channelValue   = trim((string) $request->request->get('channel', ''));
        $subject        = trim((string) $request->request->get('subject', ''));
        $bodyTemplate   = trim((string) $request->request->get('bodyTemplate', ''));
        $isActive       = (bool) $request->request->get('isActive', false);

        $eventType = EventType::tryFrom($eventTypeValue);
        $channel   = NotificationChannel::tryFrom($channelValue);

        $errors = [];
        if ($eventType === null) {
            $errors[] = 'El evento es obligatorio.';
        }
        if ($channel === null) {
            $errors[] = 'El canal es obligatorio.';
        }
        if ($bodyTemplate === '') {
            $errors[] = 'El cuerpo de la plantilla es obligatorio.';
        }

        if ($errors !== []) {
            return $this->render('notification/template/form.html.twig', [
                'template'   => null,
                'eventTypes' => EventType::cases(),
                'channels'   => NotificationChannel::cases(),
                'errors'     => $errors,
            ], new Response(null, 422));
        }

        try {
            $id = ($handler)(new CreateNotificationTemplateCommand(
                eventType:    $eventType,
                channel:      $channel,
                subject:      $subject !== '' ? $subject : null,
                bodyTemplate: $bodyTemplate,
                isActive:     $isActive,
            ));

            if ($id === null) {
                $this->addFlash('warning', sprintf(
                    'Ya existe una plantilla para el evento "%s" y canal "%s". No se creó duplicado.',
                    $eventType->value,
                    $channel->value,
                ));
            } else {
                $this->addFlash('success', 'Plantilla creada correctamente.');
            }

            return $this->redirectToRoute('notification_template_list');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('notification_template_list');
        }
    }

    #[Route('/{id}', name: 'notification_template_show', methods: ['GET'])]
    public function show(
        string $id,
        ListNotificationTemplatesHandler $handler,
        Request $request,
    ): Response {
        $templates = $handler(new ListNotificationTemplatesQuery(
            eventType: $request->query->get('eventType'),
        ));

        $template = null;
        foreach ($templates as $t) {
            if ($t->id === $id) {
                $template = $t;
                break;
            }
        }

        if ($template === null) {
            throw $this->createNotFoundException('Plantilla no encontrada.');
        }

        return $this->render('notification/template/show.html.twig', [
            'template'   => $template,
            'eventTypes' => EventType::cases(),
            'channels'   => NotificationChannel::cases(),
        ]);
    }

    #[Route('/{id}/edit', name: 'notification_template_edit', methods: ['GET'])]
    public function editForm(
        string $id,
        ListNotificationTemplatesHandler $handler,
    ): Response {
        $templates = $handler(new ListNotificationTemplatesQuery());

        $template = null;
        foreach ($templates as $t) {
            if ($t->id === $id) {
                $template = $t;
                break;
            }
        }

        if ($template === null) {
            throw $this->createNotFoundException('Plantilla no encontrada.');
        }

        return $this->render('notification/template/form.html.twig', [
            'template'   => $template,
            'eventTypes' => EventType::cases(),
            'channels'   => NotificationChannel::cases(),
            'errors'     => [],
        ]);
    }

    #[Route('/{id}', name: 'notification_template_update', methods: ['POST', 'PUT'])]
    public function update(
        string $id,
        Request $request,
        UpdateNotificationTemplateHandler $handler,
        ListNotificationTemplatesHandler $listHandler,
    ): Response {
        $subject      = trim((string) $request->request->get('subject', ''));
        $bodyTemplate = trim((string) $request->request->get('bodyTemplate', ''));
        $isActive     = (bool) $request->request->get('isActive', false);

        if ($bodyTemplate === '') {
            $templates = $listHandler(new ListNotificationTemplatesQuery());
            $template = null;
            foreach ($templates as $t) {
                if ($t->id === $id) {
                    $template = $t;
                    break;
                }
            }

            return $this->render('notification/template/form.html.twig', [
                'template'   => $template,
                'eventTypes' => EventType::cases(),
                'channels'   => NotificationChannel::cases(),
                'errors'     => ['El cuerpo de la plantilla es obligatorio.'],
            ], new Response(null, 422));
        }

        try {
            $handler(new UpdateNotificationTemplateCommand(
                templateId:  $id,
                subject:     $subject !== '' ? $subject : null,
                bodyTemplate: $bodyTemplate,
                isActive:    $isActive,
            ));

            $this->addFlash('success', 'Plantilla actualizada correctamente.');

            return $this->redirectToRoute('notification_template_show', ['id' => $id]);
        } catch (NotificationTemplateNotFoundException) {
            throw $this->createNotFoundException('Plantilla no encontrada.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('notification_template_list');
        }
    }
}
