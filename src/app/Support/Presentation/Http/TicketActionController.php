<?php

declare(strict_types=1);

namespace App\Support\Presentation\Http;

use App\Support\Application\Command\ApproveTicket\ApproveTicketCommand;
use App\Support\Application\Command\ApproveTicket\ApproveTicketHandler;
use App\Support\Application\Command\ArriveAtBase\ArriveAtBaseCommand;
use App\Support\Application\Command\ArriveAtBase\ArriveAtBaseHandler;
use App\Support\Application\Command\ArriveAtSite\ArriveAtSiteCommand;
use App\Support\Application\Command\ArriveAtSite\ArriveAtSiteHandler;
use App\Support\Application\Command\RegisterFinding\RegisterFindingCommand;
use App\Support\Application\Command\RegisterFinding\RegisterFindingHandler;
use App\Support\Application\Command\AssignTicket\AssignTicketCommand;
use App\Support\Application\Command\AssignTicket\AssignTicketHandler;
use App\Support\Application\Command\CancelTicket\CancelTicketCommand;
use App\Support\Application\Command\CancelTicket\CancelTicketHandler;
use App\Support\Application\Command\CompleteAssistance\CompleteAssistanceCommand;
use App\Support\Application\Command\CompleteAssistance\CompleteAssistanceHandler;
use App\Support\Application\Command\PauseTicket\PauseTicketCommand;
use App\Support\Application\Command\PauseTicket\PauseTicketHandler;
use App\Support\Application\Command\ResumeTicket\ResumeTicketCommand;
use App\Support\Application\Command\ResumeTicket\ResumeTicketHandler;
use App\Support\Application\Command\StartAssistanceFromWaiting\StartAssistanceFromWaitingCommand;
use App\Support\Application\Command\StartAssistanceFromWaiting\StartAssistanceFromWaitingHandler;
use App\Support\Application\Command\StartOutwardJourney\StartOutwardJourneyCommand;
use App\Support\Application\Command\StartOutwardJourney\StartOutwardJourneyHandler;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/support/tickets/{id}', name: 'support_ticket_')]
#[IsGranted('IS_AUTHENTICATED')]
final class TicketActionController extends AbstractController
{
    #[IsGranted('ticket.assign')]
    #[Route('/assign', name: 'assign', methods: ['POST'])]
    public function assign(string $id, Request $request, AssignTicketHandler $handler): Response
    {
        try {
            $employeeIds = (array) $request->request->all('employeeIds');
            $inChargeId  = (string) $request->request->get('inChargeId');
            $rawDate     = $request->request->get('scheduledAt');
            $scheduledAt = $rawDate
                ? new \DateTimeImmutable((string) $rawDate)
                : null;

            $handler(new AssignTicketCommand(
                ticketId:            $id,
                employeeIds:         $employeeIds,
                inChargeId:          $inChargeId,
                scheduledAt:         $scheduledAt,
                slaMaxResponseHours: (int) $request->request->get('slaMaxResponseHours', 0),
            ));

            $this->addFlash('success', 'Ticket asignado correctamente.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('support_ticket_show', ['id' => $id]);
    }

    #[Route('/start-journey', name: 'start_journey', methods: ['POST'])]
    public function startJourney(string $id, StartOutwardJourneyHandler $handler): Response
    {
        try {
            $handler(new StartOutwardJourneyCommand(ticketId: $id));
            $this->addFlash('success', 'Viaje de ida iniciado.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('support_ticket_show', ['id' => $id]);
    }

    #[Route('/arrive-site', name: 'arrive_site', methods: ['POST'])]
    public function arriveSite(string $id, ArriveAtSiteHandler $handler): Response
    {
        try {
            $handler(new ArriveAtSiteCommand(ticketId: $id));
            $this->addFlash('success', 'Llegada al sitio registrada.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('support_ticket_show', ['id' => $id]);
    }

    #[Route('/arrive-base', name: 'arrive_base', methods: ['POST'])]
    public function arriveBase(string $id, ArriveAtBaseHandler $handler): Response
    {
        try {
            $handler(new ArriveAtBaseCommand(ticketId: $id));
            $this->addFlash('success', 'Llegada a base registrada.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('support_ticket_show', ['id' => $id]);
    }

    #[Route('/register-finding', name: 'register_finding', methods: ['POST'])]
    public function registerFinding(string $id, Request $request, RegisterFindingHandler $handler): Response
    {
        /** @var \App\Identity\Infrastructure\Security\SymfonySecurityUser $user */
        $user = $this->getUser();

        try {
            $handler(new RegisterFindingCommand(
                ticketId:         $id,
                problemTypeFound: (string) $request->request->get('problemTypeFound', ''),
                problemDetail:    (string) $request->request->get('problemDetail', ''),
                batteryVoltageOk: (bool) $request->request->get('batteryVoltageOk', false),
                solution:         (string) $request->request->get('solution', ''),
                registeredBy:     (string) $user->getDomainUser()->id(),
                physicalDocNumber: $request->request->get('physicalDocNumber') ?: null,
                componentIds:     (array) $request->request->all('componentIds'),
            ));
            $this->addFlash('success', 'Hallazgo registrado correctamente.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('support_ticket_show', ['id' => $id]);
    }

    #[Route('/start-assistance', name: 'start_assistance', methods: ['POST'])]
    public function startAssistance(string $id, StartAssistanceFromWaitingHandler $handler): Response
    {
        try {
            $handler(new StartAssistanceFromWaitingCommand(ticketId: $id));
            $this->addFlash('success', 'Asistencia iniciada.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('support_ticket_show', ['id' => $id]);
    }

    #[Route('/pause', name: 'pause', methods: ['POST'])]
    public function pause(string $id, Request $request, PauseTicketHandler $handler): Response
    {
        /** @var \App\Identity\Infrastructure\Security\SymfonySecurityUser $user */
        $user = $this->getUser();

        try {
            $handler(new PauseTicketCommand(
                ticketId: $id,
                reason:   (string) $request->request->get('reason', ''),
                pausedBy: (string) $user->getDomainUser()->id(),
            ));
            $this->addFlash('success', 'Ticket pausado.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('support_ticket_show', ['id' => $id]);
    }

    #[Route('/resume', name: 'resume', methods: ['POST'])]
    public function resume(string $id, ResumeTicketHandler $handler): Response
    {
        try {
            $handler(new ResumeTicketCommand(ticketId: $id));
            $this->addFlash('success', 'Ticket reanudado.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('support_ticket_show', ['id' => $id]);
    }

    #[Route('/complete-assistance', name: 'complete_assistance', methods: ['POST'])]
    public function completeAssistance(string $id, CompleteAssistanceHandler $handler): Response
    {
        try {
            $handler(new CompleteAssistanceCommand(ticketId: $id));
            $this->addFlash('success', 'Asistencia completada. Pendiente de aprobación.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('support_ticket_show', ['id' => $id]);
    }

    #[IsGranted('ticket.approve')]
    #[Route('/approve', name: 'approve', methods: ['POST'])]
    public function approve(string $id, ApproveTicketHandler $handler, Connection $connection): Response
    {
        /** @var \App\Identity\Infrastructure\Security\SymfonySecurityUser $user */
        $user = $this->getUser();

        try {
            $row = $connection->fetchAssociative(
                "SELECT
                    UPPER(SUBSTRING(c.trade_name, 1, 3))  AS client_code,
                    UPPER(SUBSTRING(ci.name, 1, 3))       AS city_code,
                    COALESCE(ta.id_assigned_by_client, ta.id) AS terminal_code
                 FROM tickets tk
                 JOIN terminal_assignments ta ON ta.id = tk.terminal_assignment_id
                 JOIN sites s ON s.id = ta.site_id
                 JOIN terminals t ON t.id = ta.terminal_id
                 JOIN clients c ON c.id = t.client_id
                 JOIN cities ci ON ci.id = s.city_id
                 WHERE tk.id = :ticketId",
                ['ticketId' => $id],
            );

            if ($row === false) {
                throw new \DomainException('No se pudo obtener los datos del ticket para la aprobación.');
            }

            $handler(new ApproveTicketCommand(
                ticketId:     $id,
                approvedBy:   (string) $user->getDomainUser()->id(),
                clientCode:   (string) $row['client_code'],
                cityCode:     (string) $row['city_code'],
                terminalCode: (string) $row['terminal_code'],
            ));

            $this->addFlash('success', 'Ticket aprobado y cerrado correctamente.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('support_ticket_show', ['id' => $id]);
    }

    #[Route('/cancel', name: 'cancel', methods: ['POST'])]
    public function cancel(string $id, Request $request, CancelTicketHandler $handler): Response
    {
        /** @var \App\Identity\Infrastructure\Security\SymfonySecurityUser $user */
        $user = $this->getUser();

        try {
            $handler(new CancelTicketCommand(
                ticketId:    $id,
                reason:      (string) $request->request->get('reason', ''),
                cancelledBy: (string) $user->getDomainUser()->id(),
            ));
            $this->addFlash('success', 'Ticket cancelado.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('support_ticket_show', ['id' => $id]);
    }
}
