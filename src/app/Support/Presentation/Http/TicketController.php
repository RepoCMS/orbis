<?php

declare(strict_types=1);

namespace App\Support\Presentation\Http;

use App\Client\Application\Query\ListClients\ListClientsHandler;
use App\Client\Application\Query\ListClients\ListClientsQuery;
use App\Organization\Application\Query\ListEmployees\ListEmployeesHandler;
use App\Organization\Application\Query\ListEmployees\ListEmployeesQuery;
use App\Support\Application\Command\CreateTicket\CreateTicketCommand;
use App\Support\Application\Command\CreateTicket\CreateTicketHandler;
use App\Support\Application\Command\UpdateTicket\UpdateTicketCommand;
use App\Support\Application\Command\UpdateTicket\UpdateTicketHandler;
use App\Support\Application\Query\GetTicketDetail\GetTicketDetailHandler;
use App\Support\Application\Query\GetTicketDetail\GetTicketDetailQuery;
use App\Support\Application\Query\ListTickets\ListTicketsHandler;
use App\Support\Application\Query\ListTickets\ListTicketsQuery;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Enums\TicketStatus;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/support/tickets', name: 'support_ticket_')]
final class TicketController extends AbstractController
{
    #[IsGranted('ticket.view')]
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        Request $request,
        ListTicketsHandler $handler,
        ListClientsHandler $clientsHandler,
    ): Response {
        $page     = max(1, (int) $request->query->get('page', 1));
        $clientId = $request->query->get('clientId');
        $status   = $request->query->get('status');

        $statuses = $status ? [TicketStatus::from($status)] : [];

        $tickets = $handler(new ListTicketsQuery(
            clientId:  $clientId ?: null,
            statuses:  $statuses,
            page:      $page,
            perPage:   25,
        ));

        $clients = $clientsHandler(new ListClientsQuery(limit: 200));

        return $this->render('support/ticket/list.html.twig', [
            'tickets'  => $tickets,
            'clients'  => $clients,
            'statuses' => TicketStatus::cases(),
            'filters'  => [
                'clientId' => $clientId,
                'status'   => $status,
                'page'     => $page,
            ],
        ]);
    }

    #[IsGranted('ticket.create')]
    #[Route('/new', name: 'new', methods: ['GET'])]
    public function new(
        Request $request,
        ListClientsHandler $clientsHandler,
        Connection $connection,
    ): Response {
        $clientId = $request->query->get('clientId');

        $clients  = $clientsHandler(new ListClientsQuery(limit: 200));

        $terminals = [];
        if ($clientId) {
            $terminals = $connection->fetchAllAssociative(
                "SELECT ta.id, ta.name AS assignment_name, ta.id_assigned_by_client, s.name AS site_name
                 FROM terminal_assignments ta
                 INNER JOIN sites s ON s.id = ta.site_id AND s.deleted_at IS NULL
                 INNER JOIN terminals t ON t.id = ta.terminal_id AND t.deleted_at IS NULL AND t.client_id = :clientId
                 WHERE ta.status = 'active' AND ta.deleted_at IS NULL
                 ORDER BY s.name, ta.name",
                ['clientId' => $clientId],
            );
        }

        return $this->render('support/ticket/form.html.twig', [
            'clients'       => $clients,
            'terminals'     => $terminals,
            'serviceTypes'  => ServiceType::cases(),
            'selectedClient' => $clientId,
        ]);
    }

    #[IsGranted('ticket.create')]
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        CreateTicketHandler $handler,
    ): Response {
        /** @var \App\Identity\Infrastructure\Security\SymfonySecurityUser $user */
        $user = $this->getUser();

        try {
            $handler(new CreateTicketCommand(
                clientId:             (string) $request->request->get('clientId'),
                terminalAssignmentId: (string) $request->request->get('terminalAssignmentId'),
                serviceType:          ServiceType::from((string) $request->request->get('serviceType')),
                description:          (string) $request->request->get('description'),
                createdBy:            (string) $user->getDomainUser()->id(),
            ));

            $this->addFlash('success', 'Ticket creado correctamente.');

            return $this->redirectToRoute('support_ticket_list');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('support_ticket_new', [
                'clientId' => $request->request->get('clientId'),
            ]);
        }
    }

    #[IsGranted('ticket.view')]
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(
        string $id,
        GetTicketDetailHandler $handler,
        ListEmployeesHandler $employeesHandler,
    ): Response {
        try {
            $ticket = $handler(new GetTicketDetailQuery($id));
        } catch (\InvalidArgumentException) {
            throw $this->createNotFoundException('Ticket no encontrado.');
        }

        $employees = $employeesHandler(new ListEmployeesQuery())->items;

        return $this->render('support/ticket/show.html.twig', [
            'ticket'    => $ticket,
            'employees' => $employees,
            'statuses'  => TicketStatus::cases(),
        ]);
    }

    #[IsGranted('ticket.update')]
    #[Route('/{id}/edit', name: 'edit', methods: ['GET'])]
    public function edit(
        string $id,
        GetTicketDetailHandler $handler,
        ListClientsHandler $clientsHandler,
        Connection $connection,
    ): Response {
        try {
            $ticket = $handler(new GetTicketDetailQuery($id));
        } catch (\InvalidArgumentException) {
            throw $this->createNotFoundException('Ticket no encontrado.');
        }

        $clients   = $clientsHandler(new ListClientsQuery(limit: 200));
        $terminals = $connection->fetchAllAssociative(
            "SELECT ta.id, ta.name AS assignment_name, ta.id_assigned_by_client, s.name AS site_name
             FROM terminal_assignments ta
             INNER JOIN sites s ON s.id = ta.site_id AND s.deleted_at IS NULL
             INNER JOIN terminals t ON t.id = ta.terminal_id AND t.deleted_at IS NULL AND t.client_id = :clientId
             WHERE ta.status = 'active' AND ta.deleted_at IS NULL
             ORDER BY s.name, ta.name",
            ['clientId' => $ticket->clientId],
        );

        return $this->render('support/ticket/edit.html.twig', [
            'ticket'         => $ticket,
            'clients'        => $clients,
            'terminals'      => $terminals,
            'serviceTypes'   => ServiceType::cases(),
        ]);
    }

    #[IsGranted('ticket.update')]
    #[Route('/{id}/update', name: 'update', methods: ['POST'])]
    public function update(
        string $id,
        Request $request,
        UpdateTicketHandler $handler,
    ): Response {
        try {
            $serviceType = $request->request->get('serviceType')
                ? ServiceType::tryFrom((string) $request->request->get('serviceType'))
                : null;

            $handler(new UpdateTicketCommand(
                ticketId:    $id,
                description: $request->request->get('description') ?: null,
                serviceType: $serviceType,
            ));

            $this->addFlash('success', 'Ticket actualizado correctamente.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('support_ticket_show', ['id' => $id]);
    }
}
