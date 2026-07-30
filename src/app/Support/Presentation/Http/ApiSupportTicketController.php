<?php

declare(strict_types=1);

namespace App\Support\Presentation\Http;

use App\Identity\Infrastructure\Security\SymfonySecurityUser;
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
use App\Support\Application\Command\CreateTicket\CreateTicketCommand;
use App\Support\Application\Command\CreateTicket\CreateTicketHandler;
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
use App\Support\Application\Command\RescheduleTicket\RescheduleTicketCommand;
use App\Support\Application\Command\RescheduleTicket\RescheduleTicketHandler;
use App\Support\Application\Command\ResumeTicket\ResumeTicketCommand;
use App\Support\Application\Command\ResumeTicket\ResumeTicketHandler;
use App\Support\Application\Command\SoftDeleteTicket\SoftDeleteTicketCommand;
use App\Support\Application\Command\SoftDeleteTicket\SoftDeleteTicketHandler;
use App\Support\Application\Command\StartAssistanceFromWaiting\StartAssistanceFromWaitingCommand;
use App\Support\Application\Command\StartAssistanceFromWaiting\StartAssistanceFromWaitingHandler;
use App\Support\Application\Command\StartWaiting\StartWaitingCommand;
use App\Support\Application\Command\StartWaiting\StartWaitingHandler;
use App\Support\Application\Command\StartOutwardJourney\StartOutwardJourneyCommand;
use App\Support\Application\Command\StartOutwardJourney\StartOutwardJourneyHandler;
use App\Support\Application\Command\UpdateTicket\UpdateTicketCommand;
use App\Support\Application\Command\UpdateTicket\UpdateTicketHandler;
use App\Support\Application\Query\GetTicketDetail\GetTicketDetailHandler;
use App\Support\Application\Query\GetTicketDetail\GetTicketDetailQuery;
use App\Support\Application\Query\ListTicketPendingSpareParts\ListTicketPendingSparePartsHandler;
use App\Support\Application\Query\ListTicketPendingSpareParts\ListTicketPendingSparePartsQuery;
use App\Support\Application\Query\ListTickets\ListTicketsHandler;
use App\Support\Application\Query\ListTickets\ListTicketsQuery;
use App\Support\Domain\Enums\JourneyType;
use App\Support\Domain\Enums\RescheduleRequestedBy;
use App\Support\Domain\Enums\ServiceType;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/support/tickets')]
final class ApiSupportTicketController extends AbstractController
{
    #[IsGranted('ticket.view')]
    #[Route('', name: 'api_support_tickets_list', methods: ['GET'])]
    public function list(
        Request $request,
        ListTicketsHandler $handler,
    ): JsonResponse {
        $page     = max(1, (int) $request->query->get('page', 1));
        $limit    = max(1, min(100, (int) $request->query->get('limit', 25)));
        $clientId = $request->query->get('clientId');
        $employee = $request->query->get('employeeId');
        $status   = $request->query->get('status');
        $dateFrom = $request->query->get('dateFrom');
        $dateTo   = $request->query->get('dateTo');

        $tickets = $handler(new ListTicketsQuery(
            clientId:   $clientId ?: null,
            employeeId: $employee ?: null,
            statuses:   $status ? [\App\Support\Domain\Enums\TicketStatus::tryFrom($status)] : null,
            dateFrom:   $dateFrom ?: null,
            dateTo:     $dateTo ?: null,
            page:       $page,
            perPage:    $limit,
        ));

        return $this->json(['data' => $tickets]);
    }

    #[IsGranted('ticket.create')]
    #[Route('', name: 'api_support_tickets_create', methods: ['POST'])]
    public function create(
        Request $request,
        CreateTicketHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        /** @var SymfonySecurityUser $user */
        $user = $this->getUser();

        try {
            $serviceType = ServiceType::from((string) ($payload['serviceType'] ?? ''));
        } catch (\ValueError) {
            return $this->json(
                ['error' => 'El campo serviceType no es válido. Valores permitidos: ' . implode(', ', array_map(fn($c) => $c->value, ServiceType::cases()))],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        try {
            $ticketId = $handler(new CreateTicketCommand(
                clientId:             trim((string) ($payload['clientId'] ?? '')),
                terminalAssignmentId: trim((string) ($payload['terminalAssignmentId'] ?? '')),
                serviceType:          $serviceType,
                description:          trim((string) ($payload['description'] ?? '')),
                createdBy:            (string) $user->getDomainUser()->id(),
            ));

            return $this->json(['data' => ['id' => $ticketId]], Response::HTTP_CREATED);
        } catch (DomainException | \DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('ticket.view')]
    #[Route('/{id}', name: 'api_support_tickets_show', methods: ['GET'])]
    public function show(
        string $id,
        GetTicketDetailHandler $handler,
    ): JsonResponse {
        try {
            $ticket = $handler(new GetTicketDetailQuery($id));

            return $this->json(['data' => $ticket]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Ticket no encontrado.'], Response::HTTP_NOT_FOUND);
        }
    }

    #[IsGranted('ticket.assign')]
    #[Route('/{id}/assign', name: 'api_support_tickets_assign', methods: ['POST'])]
    public function assign(
        string $id,
        Request $request,
        AssignTicketHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            $scheduledAt = isset($payload['scheduledAt'])
                ? new \DateTimeImmutable((string) $payload['scheduledAt'])
                : null;

            $handler(new AssignTicketCommand(
                ticketId:            $id,
                employeeIds:         (array) ($payload['employeeIds'] ?? []),
                inChargeId:          trim((string) ($payload['inChargeId'] ?? '')),
                scheduledAt:         $scheduledAt,
                slaMaxResponseHours: (int) ($payload['slaMaxResponseHours'] ?? 0),
            ));

            return $this->json(['data' => ['id' => $id, 'status' => 'assigned']]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Ticket no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException | \DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('ticket.assistance')]
    #[Route('/{id}/start-journey', name: 'api_support_tickets_start_journey', methods: ['POST'])]
    public function startJourney(
        string $id,
        Request $request,
        StartOutwardJourneyHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        $journeyType = isset($payload['type'])
            ? JourneyType::tryFrom((string) $payload['type'])
            : JourneyType::OUTWARD;

        try {
            $handler(new StartOutwardJourneyCommand(
                ticketId:     $id,
                journeyType:  $journeyType ?? JourneyType::OUTWARD,
                isPartOfRoute: (bool) ($payload['isPartOfRoute'] ?? false),
                notes:        isset($payload['notes']) ? trim((string) $payload['notes']) : null,
            ));

            return $this->json(['data' => ['id' => $id, 'status' => 'in_transit_out']]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Ticket no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException | \DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('ticket.assistance')]
    #[Route('/{id}/arrive-site', name: 'api_support_tickets_arrive_site', methods: ['POST'])]
    public function arriveSite(
        string $id,
        ArriveAtSiteHandler $handler,
    ): JsonResponse {
        try {
            $handler(new ArriveAtSiteCommand(ticketId: $id));

            return $this->json(['data' => ['id' => $id]]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Ticket no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException | \DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('ticket.assistance')]
    #[Route('/{id}/arrive-base', name: 'api_support_tickets_arrive_base', methods: ['POST'])]
    public function arriveBase(
        string $id,
        ArriveAtBaseHandler $handler,
    ): JsonResponse {
        try {
            $handler(new ArriveAtBaseCommand(ticketId: $id));

            return $this->json(['data' => ['id' => $id]]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Ticket no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException | \DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('ticket.assistance')]
    #[Route('/{id}/register-finding', name: 'api_support_tickets_register_finding', methods: ['POST'])]
    public function registerFinding(
        string $id,
        Request $request,
        RegisterFindingHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        /** @var SymfonySecurityUser $user */
        $user = $this->getUser();

        try {
            $handler(new RegisterFindingCommand(
                ticketId:          $id,
                problemTypeFound:  trim((string) ($payload['problemTypeFound'] ?? '')),
                problemDetail:     trim((string) ($payload['problemDetail'] ?? '')),
                batteryVoltageOk:  (bool) ($payload['batteryVoltageOk'] ?? false),
                solution:          trim((string) ($payload['solution'] ?? '')),
                registeredBy:      (string) $user->getDomainUser()->id(),
                physicalDocNumber: isset($payload['physicalDocNumber']) ? trim((string) $payload['physicalDocNumber']) : null,
                componentIds:      (array) ($payload['componentIds'] ?? []),
            ));

            return $this->json(['data' => ['id' => $id]]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Ticket no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException | \DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('ticket.assistance')]
    #[Route('/{id}/start-waiting', name: 'api_support_tickets_start_waiting', methods: ['POST'])]
    public function startWaiting(
        string $id,
        StartWaitingHandler $handler,
    ): JsonResponse {
        try {
            $handler(new StartWaitingCommand(ticketId: $id));

            return $this->json(['data' => ['id' => $id, 'status' => 'waiting']]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Ticket no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException | \DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('ticket.assistance')]
    #[Route('/{id}/start-assistance', name: 'api_support_tickets_start_assistance', methods: ['POST'])]
    public function startAssistance(
        string $id,
        StartAssistanceFromWaitingHandler $handler,
    ): JsonResponse {
        try {
            $handler(new StartAssistanceFromWaitingCommand(ticketId: $id));

            return $this->json(['data' => ['id' => $id, 'status' => 'in_assistance']]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Ticket no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException | \DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('ticket.assistance')]
    #[Route('/{id}/pause', name: 'api_support_tickets_pause', methods: ['POST'])]
    public function pause(
        string $id,
        Request $request,
        PauseTicketHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        /** @var SymfonySecurityUser $user */
        $user = $this->getUser();

        try {
            $handler(new PauseTicketCommand(
                ticketId: $id,
                reason:   trim((string) ($payload['reason'] ?? '')),
                pausedBy: (string) $user->getDomainUser()->id(),
            ));

            return $this->json(['data' => ['id' => $id, 'status' => 'paused']]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Ticket no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException | \DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('ticket.assistance')]
    #[Route('/{id}/resume', name: 'api_support_tickets_resume', methods: ['POST'])]
    public function resume(
        string $id,
        ResumeTicketHandler $handler,
    ): JsonResponse {
        try {
            $handler(new ResumeTicketCommand(ticketId: $id));

            return $this->json(['data' => ['id' => $id, 'status' => 'in_assistance']]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Ticket no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException | \DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('ticket.assistance')]
    #[Route('/{id}/complete-assistance', name: 'api_support_tickets_complete_assistance', methods: ['POST'])]
    public function completeAssistance(
        string $id,
        CompleteAssistanceHandler $handler,
    ): JsonResponse {
        try {
            $handler(new CompleteAssistanceCommand(ticketId: $id));

            return $this->json(['data' => ['id' => $id, 'status' => 'pending_closure']]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Ticket no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException | \DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('ticket.approve')]
    #[Route('/{id}/approve', name: 'api_support_tickets_approve', methods: ['POST'])]
    public function approve(
        string $id,
        Request $request,
        ApproveTicketHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        /** @var SymfonySecurityUser $user */
        $user = $this->getUser();

        try {
            $handler(new ApproveTicketCommand(
                ticketId:     $id,
                approvedBy:   (string) $user->getDomainUser()->id(),
                clientCode:   trim((string) ($payload['clientCode'] ?? '')),
                cityCode:     trim((string) ($payload['cityCode'] ?? '')),
                terminalCode: trim((string) ($payload['terminalCode'] ?? '')),
            ));

            return $this->json(['data' => ['id' => $id, 'status' => 'closed']]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Ticket no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException | \DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('ticket.cancel')]
    #[Route('/{id}/cancel', name: 'api_support_tickets_cancel', methods: ['POST'])]
    public function cancel(
        string $id,
        Request $request,
        CancelTicketHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            $handler(new CancelTicketCommand(
                ticketId:    $id,
                reason:      trim((string) ($payload['reason'] ?? '')),
                cancelledBy: trim((string) ($payload['cancelledBy'] ?? '')),
            ));

            return $this->json(['data' => ['id' => $id, 'status' => 'cancelled']]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Ticket no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException | \DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('ticket.reschedule')]
    #[Route('/{id}/reschedule', name: 'api_support_tickets_reschedule', methods: ['POST'])]
    public function reschedule(
        string $id,
        Request $request,
        RescheduleTicketHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            $requestedBy = RescheduleRequestedBy::tryFrom((string) ($payload['requestedBy'] ?? ''));
            if ($requestedBy === null) {
                return $this->json(
                    ['error' => 'El campo requestedBy debe ser CLIENT o CMS.'],
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            $handler(new RescheduleTicketCommand(
                ticketId:            $id,
                newScheduledAt:      new \DateTimeImmutable((string) ($payload['newScheduledAt'] ?? '')),
                reason:              trim((string) ($payload['reason'] ?? '')),
                requestedBy:         $requestedBy,
                rescheduledBy:       trim((string) (isset($payload['rescheduledBy']) ? $payload['rescheduledBy'] : $this->getCurrentUserId())),
                slaMaxResponseHours: (int) ($payload['slaMaxResponseHours'] ?? 0),
            ));

            return $this->json(['data' => ['id' => $id, 'status' => 'RESCHEDULED']]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Ticket no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException | \DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('ticket.assistance')]
    #[Route('/{id}/spare-parts/request', name: 'api_support_tickets_spare_parts_request', methods: ['POST'])]
    public function requestPendingSparePart(
        string $id,
        Request $request,
        RequestPendingSparePartHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        /** @var SymfonySecurityUser $user */
        $user = $this->getUser();

        try {
            $handler(new RequestPendingSparePartCommand(
                ticketId:    $id,
                productCode: trim((string) ($payload['productCode'] ?? '')),
                quantity:    (int) ($payload['quantity'] ?? 1),
                notes:       isset($payload['notes']) ? trim((string) $payload['notes']) : null,
                userId:      (string) $user->getDomainUser()->id(),
            ));

            return $this->json(['data' => ['id' => $id, 'status' => 'pending_spare_part_requested']]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (DomainException | \DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('ticket.assistance')]
    #[Route('/{id}/spare-parts/register', name: 'api_support_tickets_spare_parts_register', methods: ['POST'])]
    public function registerSparePart(
        string $id,
        Request $request,
        RegisterSparePartHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        /** @var SymfonySecurityUser $user */
        $user = $this->getUser();

        try {
            $handler(new RegisterSparePartCommand(
                ticketId:          $id,
                findingId:         trim((string) ($payload['findingId'] ?? '')),
                productCode:       trim((string) ($payload['productCode'] ?? '')),
                userId:            (string) $user->getDomainUser()->id(),
                serialNumber:      isset($payload['serialNumber']) ? trim((string) $payload['serialNumber']) : null,
                quantity:          (int) ($payload['quantity'] ?? 1),
                isIncoming:        (bool) ($payload['isIncoming'] ?? true),
                isVisibleToClient: (bool) ($payload['isVisibleToClient'] ?? true),
            ));

            return $this->json(['data' => ['id' => $id, 'status' => 'spare_part_registered']]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (DomainException | \DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('ticket.view')]
    #[Route('/{id}/spare-parts/pending', name: 'api_support_tickets_spare_parts_pending', methods: ['GET'])]
    public function listPendingSpareParts(
        string $id,
        ListTicketPendingSparePartsHandler $handler,
    ): JsonResponse {
        try {
            $parts = $handler(new ListTicketPendingSparePartsQuery(
                ticketId: $id,
                userId: $this->getCurrentUserId() ?: null,
            ));

            return $this->json(['data' => $parts]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Ticket no encontrado.'], Response::HTTP_NOT_FOUND);
        }
    }

    #[IsGranted('ticket.update')]
    #[Route('/{id}', name: 'api_support_tickets_update', methods: ['PUT'])]
    public function updateTicket(
        string $id,
        Request $request,
        UpdateTicketHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            $serviceType = isset($payload['serviceType'])
                ? ServiceType::tryFrom((string) $payload['serviceType'])
                : null;

            $handler(new UpdateTicketCommand(
                ticketId:    $id,
                description: isset($payload['description']) ? trim((string) $payload['description']) : null,
                serviceType: $serviceType,
            ));

            return $this->json(['data' => ['id' => $id]]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Ticket no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException | \DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('ticket.update')]
    #[Route('/{id}/reassign', name: 'api_support_tickets_reassign', methods: ['PATCH'])]
    public function reassignTicket(
        string $id,
        Request $request,
        ReassignTicketHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        /** @var SymfonySecurityUser $user */
        $user = $this->getUser();

        try {
            $scheduledAt = isset($payload['scheduledAt'])
                ? new \DateTimeImmutable((string) $payload['scheduledAt'])
                : null;

            $handler(new ReassignTicketCommand(
                ticketId:           $id,
                newEmployeeId:      trim((string) ($payload['newEmployeeId'] ?? '')),
                previousEmployeeId: trim((string) ($payload['previousEmployeeId'] ?? '')),
                reassignedBy:       (string) $user->getDomainUser()->id(),
                scheduledAt:        $scheduledAt,
            ));

            return $this->json(['data' => ['id' => $id, 'status' => 'reassigned']]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Ticket no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException | \DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('ticket.update')]
    #[Route('/{id}/evidence', name: 'api_support_tickets_evidence', methods: ['POST'])]
    public function addEvidence(
        string $id,
        Request $request,
        AddEvidenceHandler $handler,
    ): JsonResponse {
        /** @var SymfonySecurityUser $user */
        $user = $this->getUser();

        $uploadedFile = $request->files->get('file');
        if ($uploadedFile === null) {
            return $this->json(['error' => 'El archivo es requerido.'], Response::HTTP_BAD_REQUEST);
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

            return $this->json(['data' => ['id' => $id, 'evidence' => $fileName]]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Ticket no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException | \DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('ticket.view')]
    #[Route('/{id}/request-reopen', name: 'api_support_tickets_request_reopen', methods: ['POST'])]
    public function requestReopenTicket(
        string $id,
        Request $request,
        RequestReopenTicketHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        /** @var SymfonySecurityUser $user */
        $user = $this->getUser();

        try {
            $handler(new RequestReopenTicketCommand(
                ticketId:    $id,
                reason:      trim((string) ($payload['reason'] ?? '')),
                requestedBy: (string) $user->getDomainUser()->id(),
            ));

            return $this->json(['data' => ['id' => $id, 'status' => 'reopen_requested']]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Ticket no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException | \DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('ticket.approve')]
    #[Route('/{id}/approve-reopen', name: 'api_support_tickets_approve_reopen', methods: ['POST'])]
    public function approveReopenTicket(
        string $id,
        ApproveReopenTicketHandler $handler,
    ): JsonResponse {
        /** @var SymfonySecurityUser $user */
        $user = $this->getUser();

        try {
            $handler(new ApproveReopenTicketCommand(
                ticketId:   $id,
                approvedBy: (string) $user->getDomainUser()->id(),
            ));

            return $this->json(['data' => ['id' => $id, 'status' => 'reopened']]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Ticket no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException | \DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('ticket.update')]
    #[Route('/{id}', name: 'api_support_tickets_delete', methods: ['DELETE'])]
    public function deleteTicket(
        string $id,
        SoftDeleteTicketHandler $handler,
    ): JsonResponse {
        try {
            $handler(new SoftDeleteTicketCommand(
                ticketId: $id,
            ));

            return $this->json(['data' => ['id' => $id, 'status' => 'deleted']]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Ticket no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException | \DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Returns the current authenticated user's domain ID (ULID).
     */
    private function getCurrentUserId(): string
    {
        $user = $this->getUser();
        if ($user instanceof SymfonySecurityUser) {
            return (string) $user->getDomainUser()->id();
        }

        return '';
    }
}
