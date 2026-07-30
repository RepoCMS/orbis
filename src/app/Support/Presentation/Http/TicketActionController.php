<?php

declare(strict_types=1);

namespace App\Support\Presentation\Http;

use App\Support\Application\Command\AddEvidence\AddEvidenceCommand;
use App\Support\Application\Command\AddEvidence\AddEvidenceHandler;
use App\Support\Application\Command\ApproveReopenTicket\ApproveReopenTicketCommand;
use App\Support\Application\Command\ApproveReopenTicket\ApproveReopenTicketHandler;
use App\Support\Application\Command\ApproveTicket\ApproveTicketCommand;
use App\Support\Application\Command\ApproveTicket\ApproveTicketHandler;
use App\Support\Application\Command\ArriveAtBase\ArriveAtBaseCommand;
use App\Support\Application\Command\ArriveAtBase\ArriveAtBaseHandler;
use App\Support\Application\Command\ArriveAtSite\ArriveAtSiteCommand;
use App\Support\Application\Command\ArriveAtSite\ArriveAtSiteHandler;
use App\Support\Application\Command\AssignTicket\AssignTicketCommand;
use App\Support\Application\Command\AssignTicket\AssignTicketHandler;
use App\Support\Application\Command\CancelTicket\CancelTicketCommand;
use App\Support\Application\Command\CancelTicket\CancelTicketHandler;
use App\Support\Application\Command\CompleteAssistance\CompleteAssistanceCommand;
use App\Support\Application\Command\CompleteAssistance\CompleteAssistanceHandler;
use App\Support\Application\Command\PauseTicket\PauseTicketCommand;
use App\Support\Application\Command\PauseTicket\PauseTicketHandler;
use App\Support\Application\Command\ReassignTicket\ReassignTicketCommand;
use App\Support\Application\Command\ReassignTicket\ReassignTicketHandler;
use App\Support\Application\Command\RegisterFinding\RegisterFindingCommand;
use App\Support\Application\Command\RegisterFinding\RegisterFindingHandler;
use App\Support\Application\Command\RegisterSparePart\RegisterSparePartCommand;
use App\Support\Application\Command\RegisterSparePart\RegisterSparePartHandler;
use App\Support\Application\Command\RequestPendingSparePart\RequestPendingSparePartCommand;
use App\Support\Application\Command\RequestPendingSparePart\RequestPendingSparePartHandler;
use App\Support\Application\Command\RequestReopenTicket\RequestReopenTicketCommand;
use App\Support\Application\Command\RequestReopenTicket\RequestReopenTicketHandler;
use App\Support\Application\Command\ResumeTicket\ResumeTicketCommand;
use App\Support\Application\Command\ResumeTicket\ResumeTicketHandler;
use App\Support\Application\Command\SoftDeleteTicket\SoftDeleteTicketCommand;
use App\Support\Application\Command\SoftDeleteTicket\SoftDeleteTicketHandler;
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

    #[Route('/spare-parts/request', name: 'spare_parts_request', methods: ['POST'])]
    public function requestPendingSparePart(string $id, Request $request, RequestPendingSparePartHandler $handler): Response
    {
        /** @var \App\Identity\Infrastructure\Security\SymfonySecurityUser $user */
        $user = $this->getUser();

        try {
            $handler(new RequestPendingSparePartCommand(
                ticketId:    $id,
                productCode: (string) $request->request->get('productCode', ''),
                quantity:    (int) $request->request->get('quantity', 1),
                notes:       $request->request->get('notes') ?: null,
                userId:      (string) $user->getDomainUser()->id(),
            ));
            $this->addFlash('success', 'Repuesto solicitado correctamente.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('support_ticket_show', ['id' => $id]);
    }

    #[Route('/spare-parts/register', name: 'spare_parts_register', methods: ['POST'])]
    public function registerSparePart(string $id, Request $request, RegisterSparePartHandler $handler): Response
    {
        /** @var \App\Identity\Infrastructure\Security\SymfonySecurityUser $user */
        $user = $this->getUser();

        try {
            $handler(new RegisterSparePartCommand(
                ticketId:          $id,
                findingId:         (string) $request->request->get('findingId', ''),
                productCode:       (string) $request->request->get('productCode', ''),
                userId:            (string) $user->getDomainUser()->id(),
                serialNumber:      $request->request->get('serialNumber') ?: null,
                quantity:          (int) $request->request->get('quantity', 1),
                isIncoming:        (bool) $request->request->get('isIncoming', true),
                isVisibleToClient: (bool) $request->request->get('isVisibleToClient', true),
            ));
            $this->addFlash('success', 'Repuesto registrado correctamente.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('support_ticket_show', ['id' => $id]);
    }

    #[Route('/reassign', name: 'reassign', methods: ['POST'])]
    public function reassignTicket(string $id, Request $request, ReassignTicketHandler $handler): Response
    {
        /** @var \App\Identity\Infrastructure\Security\SymfonySecurityUser $user */
        $user = $this->getUser();

        try {
            $scheduledAt = $request->request->get('scheduledAt')
                ? new \DateTimeImmutable((string) $request->request->get('scheduledAt'))
                : null;

            $handler(new ReassignTicketCommand(
                ticketId:           $id,
                newEmployeeId:      (string) $request->request->get('newEmployeeId', ''),
                previousEmployeeId: (string) $request->request->get('previousEmployeeId', ''),
                reassignedBy:       (string) $user->getDomainUser()->id(),
                scheduledAt:        $scheduledAt,
            ));
            $this->addFlash('success', 'Ticket reasignado correctamente.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('support_ticket_show', ['id' => $id]);
    }

    #[Route('/evidence', name: 'evidence', methods: ['POST'])]
    public function addEvidence(string $id, Request $request, AddEvidenceHandler $handler): Response
    {
        /** @var \App\Identity\Infrastructure\Security\SymfonySecurityUser $user */
        $user = $this->getUser();

        $uploadedFile = $request->files->get('file');
        if ($uploadedFile === null) {
            $this->addFlash('error', 'El archivo es requerido.');
            return $this->redirectToRoute('support_ticket_show', ['id' => $id]);
        }

        try {
            $uploadsDir = $this->getParameter('kernel.project_dir') . '/var/uploads/evidence';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }

            $fileName = sprintf('%s_%s_%s', $id, (new \DateTimeImmutable())->format('YmdHis'), $uploadedFile->getClientOriginalName());
            $filePath = $uploadsDir . '/' . $fileName;
            $uploadedFile->move($uploadsDir, $fileName);

            $handler(new AddEvidenceCommand(
                ticketId:   $id,
                filePath:   $filePath,
                uploadedBy: (string) $user->getDomainUser()->id(),
            ));
            $this->addFlash('success', 'Evidencia subida correctamente.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('support_ticket_show', ['id' => $id]);
    }

    #[Route('/request-reopen', name: 'request_reopen', methods: ['POST'])]
    public function requestReopenTicket(string $id, Request $request, RequestReopenTicketHandler $handler): Response
    {
        /** @var \App\Identity\Infrastructure\Security\SymfonySecurityUser $user */
        $user = $this->getUser();

        try {
            $handler(new RequestReopenTicketCommand(
                ticketId:    $id,
                reason:      (string) $request->request->get('reason', ''),
                requestedBy: (string) $user->getDomainUser()->id(),
            ));
            $this->addFlash('success', 'Solicitud de reapertura enviada correctamente.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('support_ticket_show', ['id' => $id]);
    }

    #[IsGranted('ticket.approve')]
    #[Route('/approve-reopen', name: 'approve_reopen', methods: ['POST'])]
    public function approveReopenTicket(string $id, ApproveReopenTicketHandler $handler): Response
    {
        /** @var \App\Identity\Infrastructure\Security\SymfonySecurityUser $user */
        $user = $this->getUser();

        try {
            $handler(new ApproveReopenTicketCommand(
                ticketId:   $id,
                approvedBy: (string) $user->getDomainUser()->id(),
            ));
            $this->addFlash('success', 'Reapertura aprobada correctamente.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('support_ticket_show', ['id' => $id]);
    }

    #[Route('/delete', name: 'delete', methods: ['POST'])]
    public function deleteTicket(string $id, SoftDeleteTicketHandler $handler): Response
    {
        try {
            $handler(new SoftDeleteTicketCommand(
                ticketId: $id,
            ));
            $this->addFlash('success', 'Ticket eliminado correctamente.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('support_ticket_list');
    }
}
