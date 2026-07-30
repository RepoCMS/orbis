<?php

declare(strict_types=1);

namespace App\Support\Presentation\Http;

use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Infrastructure\Security\SymfonySecurityUser;
use App\Support\Application\Query\GetTicketDetail\GetTicketDetailHandler;
use App\Support\Application\Query\GetTicketDetail\GetTicketDetailQuery;
use App\Support\Application\Query\ListTickets\ListTicketsHandler;
use App\Support\Application\Query\ListTickets\ListTicketsQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Portal web para contactos de cliente.
 * Los contactos autenticados pueden ver solo los tickets de su cliente.
 */
#[Route('/portal/support')]
final class ClientPortalController extends AbstractController
{
    #[IsGranted('ticket.view')]
    #[Route('/tickets', name: 'portal_support_tickets_list', methods: ['GET'])]
    public function list(
        Request $request,
        ListTicketsHandler $handler,
        ContactRepositoryInterface $contactRepo,
    ): Response {
        $clientId = $this->resolveClientId($contactRepo);

        if ($clientId === null) {
            return $this->render('support/portal/tickets.html.twig', [
                'tickets' => [],
                'error'   => 'No se encontró un cliente asociado a tu usuario.',
            ]);
        }

        $page     = max(1, (int) $request->query->get('page', 1));
        $limit    = max(1, min(50, (int) $request->query->get('limit', 25)));
        $status   = $request->query->get('status');

        $tickets = $handler(new ListTicketsQuery(
            clientId: $clientId,
            page:     $page,
            perPage:  $limit,
            statuses: $status ? [\App\Support\Domain\Enums\TicketStatus::tryFrom($status)] : null,
        ));

        return $this->render('support/portal/tickets.html.twig', [
            'tickets' => $tickets,
            'status'  => $status,
        ]);
    }

    #[IsGranted('ticket.view')]
    #[Route('/tickets/{id}', name: 'portal_support_tickets_show', methods: ['GET'])]
    public function show(
        string $id,
        GetTicketDetailHandler $handler,
        ContactRepositoryInterface $contactRepo,
    ): Response {
        $clientId = $this->resolveClientId($contactRepo);

        if ($clientId === null) {
            throw $this->createAccessDeniedException('No tienes acceso a este portal.');
        }

        try {
            $ticket = $handler(new GetTicketDetailQuery($id));
        } catch (\InvalidArgumentException | \App\Shared\Exceptions\ApplicationException $e) {
            throw $this->createNotFoundException('Ticket no encontrado.');
        }

        // Verificar que el ticket pertenezca al cliente del contacto
        if ($ticket->clientId !== $clientId) {
            throw $this->createAccessDeniedException('No tienes permiso para ver este ticket.');
        }

        return $this->render('support/portal/show.html.twig', [
            'ticket' => $ticket,
        ]);
    }

    /**
     * Resuelve el clientId del contacto autenticado.
     */
    private function resolveClientId(ContactRepositoryInterface $contactRepo): ?string
    {
        $user = $this->getUser();
        if (!$user instanceof SymfonySecurityUser) {
            return null;
        }

        $userId = new UserIdValueObject((string) $user->getDomainUser()->id());
        $contact = $contactRepo->findByUserId($userId);

        if ($contact === null) {
            return null;
        }

        return (string) $contact->clientId();
    }
}
