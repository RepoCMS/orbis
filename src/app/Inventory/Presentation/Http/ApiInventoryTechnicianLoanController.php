<?php

declare(strict_types=1);

namespace App\Inventory\Presentation\Http;

use App\Inventory\Application\Command\ApproveTechnicianLoan\ApproveTechnicianLoanCommand;
use App\Inventory\Application\Command\ApproveTechnicianLoan\ApproveTechnicianLoanHandler;
use App\Inventory\Application\Command\CancelTechnicianLoan\CancelTechnicianLoanCommand;
use App\Inventory\Application\Command\CancelTechnicianLoan\CancelTechnicianLoanHandler;
use App\Inventory\Application\Command\CreateTechnicianLoan\CreateTechnicianLoanCommand;
use App\Inventory\Application\Command\CreateTechnicianLoan\CreateTechnicianLoanHandler;
use App\Inventory\Application\Command\RejectTechnicianLoan\RejectTechnicianLoanCommand;
use App\Inventory\Application\Command\RejectTechnicianLoan\RejectTechnicianLoanHandler;
use App\Inventory\Application\Query\ListTechnicianLoans\ListTechnicianLoansHandler;
use App\Inventory\Application\Query\ListTechnicianLoans\ListTechnicianLoansQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/inventory/loans')]
final class ApiInventoryTechnicianLoanController extends AbstractController
{
    #[IsGranted('inventory.create')]
    #[Route('', name: 'api_inventory_technician_loans_create', methods: ['POST'])]
    public function create(
        Request $request,
        CreateTechnicianLoanHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            ($handler)(new CreateTechnicianLoanCommand(
                serializedStockId: trim((string) ($payload['serializedStockId'] ?? '')),
                fromTechnicianId:  trim((string) ($payload['fromTechnicianId'] ?? '')),
                toTechnicianId:    trim((string) ($payload['toTechnicianId'] ?? '')),
                ticketId:          isset($payload['ticketId']) ? trim((string) $payload['ticketId']) : null,
                notes:             isset($payload['notes']) ? trim((string) $payload['notes']) : null,
            ));

            return $this->json(['data' => ['status' => 'created']], Response::HTTP_CREATED);
        } catch (\InvalidArgumentException | \LogicException | \DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.view')]
    #[Route('', name: 'api_inventory_technician_loans_list', methods: ['GET'])]
    public function list(
        Request $request,
        ListTechnicianLoansHandler $handler,
    ): JsonResponse {
        $status        = $request->query->get('status');
        $technicianId  = $request->query->get('technicianId');

        $loans = $handler(new ListTechnicianLoansQuery(
            status:        $status !== null ? (string) $status : null,
            technicianId:  $technicianId !== null ? (string) $technicianId : null,
        ));

        return $this->json(['data' => $loans]);
    }

    #[IsGranted('inventory.update')]
    #[Route('/{id}/approve', name: 'api_inventory_technician_loans_approve', methods: ['POST'])]
    public function approve(
        string $id,
        Request $request,
        ApproveTechnicianLoanHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            ($handler)(new ApproveTechnicianLoanCommand(
                loanId:     $id,
                approvedBy: trim((string) ($payload['approvedBy'] ?? '')),
            ));

            return $this->json(['data' => ['status' => 'approved']]);
        } catch (\InvalidArgumentException | \LogicException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.update')]
    #[Route('/{id}/reject', name: 'api_inventory_technician_loans_reject', methods: ['POST'])]
    public function reject(
        string $id,
        Request $request,
        RejectTechnicianLoanHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            ($handler)(new RejectTechnicianLoanCommand(
                loanId:     $id,
                reason:     trim((string) ($payload['reason'] ?? '')),
                rejectedBy: isset($payload['rejectedBy']) ? trim((string) $payload['rejectedBy']) : null,
            ));

            return $this->json(['data' => ['status' => 'rejected']]);
        } catch (\InvalidArgumentException | \LogicException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.update')]
    #[Route('/{id}/cancel', name: 'api_inventory_technician_loans_cancel', methods: ['POST'])]
    public function cancel(
        string $id,
        Request $request,
        CancelTechnicianLoanHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            ($handler)(new CancelTechnicianLoanCommand(
                loanId:      $id,
                cancelledBy: isset($payload['cancelledBy']) ? trim((string) $payload['cancelledBy']) : null,
            ));

            return $this->json(['data' => ['status' => 'cancelled']]);
        } catch (\InvalidArgumentException | \LogicException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
