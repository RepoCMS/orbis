<?php

declare(strict_types=1);

namespace App\Inventory\Presentation\Http;

use App\Inventory\Application\Command\CreateCustodyAssignment\CreateCustodyAssignmentCommand;
use App\Inventory\Application\Command\CreateCustodyAssignment\CreateCustodyAssignmentHandler;
use App\Inventory\Application\Command\ReturnFromCustody\ReturnFromCustodyCommand;
use App\Inventory\Application\Command\ReturnFromCustody\ReturnFromCustodyHandler;
use App\Inventory\Application\Exception\LocationNotFoundException;
use App\Inventory\Application\Exception\StockNotFoundException;
use App\Inventory\Application\Query\ListCustodyAssignments\ListCustodyAssignmentsHandler;
use App\Inventory\Application\Query\ListCustodyAssignments\ListCustodyAssignmentsQuery;
use App\Shared\Exceptions\ApplicationException;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/inventory/custody')]
final class ApiInventoryCustodyController extends AbstractController
{
    #[IsGranted('inventory.create')]
    #[Route('/assign', name: 'api_inventory_custody_assign', methods: ['POST'])]
    public function assign(
        Request $request,
        CreateCustodyAssignmentHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            ($handler)(new CreateCustodyAssignmentCommand(
                employeeId:  trim((string) ($payload['employeeId'] ?? '')),
                stockItemId: trim((string) ($payload['stockItemId'] ?? '')),
                locationId:  trim((string) ($payload['locationId'] ?? '')),
                notes:       isset($payload['notes']) ? trim((string) $payload['notes']) : null,
            ));

            return $this->json(['data' => ['status' => 'assigned']], Response::HTTP_CREATED);
        } catch (LocationNotFoundException | StockNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (ApplicationException | DomainException | \InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.create')]
    #[Route('/return', name: 'api_inventory_custody_return', methods: ['POST'])]
    public function return(
        Request $request,
        ReturnFromCustodyHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            ($handler)(new ReturnFromCustodyCommand(
                custodyLocationId: trim((string) ($payload['custodyLocationId'] ?? '')),
                stockItemId:       trim((string) ($payload['stockItemId'] ?? '')),
                returnLocationId:  trim((string) ($payload['returnLocationId'] ?? '')),
            ));

            return $this->json(['data' => ['status' => 'returned']], Response::HTTP_OK);
        } catch (LocationNotFoundException | StockNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (ApplicationException | DomainException | \InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.view')]
    #[Route('', name: 'api_inventory_custody_list', methods: ['GET'])]
    public function list(
        Request $request,
        ListCustodyAssignmentsHandler $handler,
    ): JsonResponse {
        $employeeId = $request->query->get('employeeId');
        $locationId = $request->query->get('locationId');

        $assignments = $handler(new ListCustodyAssignmentsQuery(
            employeeId: $employeeId !== null ? (string) $employeeId : null,
            locationId: $locationId !== null ? (string) $locationId : null,
        ));

        return $this->json(['data' => $assignments]);
    }

    #[IsGranted('inventory.view')]
    #[Route('/by-employee/{employeeId}', name: 'api_inventory_custody_by_employee', methods: ['GET'])]
    public function byEmployee(
        string $employeeId,
        ListCustodyAssignmentsHandler $handler,
    ): JsonResponse {
        $assignments = $handler(new ListCustodyAssignmentsQuery(employeeId: $employeeId));

        return $this->json(['data' => $assignments]);
    }
}
