<?php

declare(strict_types=1);

namespace App\Inventory\Presentation\Http;

use App\Inventory\Application\Command\AssignRepair\AssignRepairCommand;
use App\Inventory\Application\Command\AssignRepair\AssignRepairHandler;
use App\Inventory\Application\Command\AttachEvidence\AttachEvidenceCommand;
use App\Inventory\Application\Command\AttachEvidence\AttachEvidenceHandler;
use App\Inventory\Application\Command\CompleteRepair\CompleteRepairCommand;
use App\Inventory\Application\Command\CompleteRepair\CompleteRepairHandler;
use App\Inventory\Application\Command\MarkRepairUnrepairable\MarkRepairUnrepairableCommand;
use App\Inventory\Application\Command\MarkRepairUnrepairable\MarkRepairUnrepairableHandler;
use App\Inventory\Application\Command\RegisterConsumable\RegisterConsumableCommand;
use App\Inventory\Application\Command\RegisterConsumable\RegisterConsumableHandler;
use App\Inventory\Application\Command\RegisterEmergencyPartUsage\RegisterEmergencyPartUsageCommand;
use App\Inventory\Application\Command\RegisterEmergencyPartUsage\RegisterEmergencyPartUsageHandler;
use App\Inventory\Application\Command\RemoveConsumable\RemoveConsumableCommand;
use App\Inventory\Application\Command\RemoveConsumable\RemoveConsumableHandler;
use App\Inventory\Application\Command\RemoveEvidence\RemoveEvidenceCommand;
use App\Inventory\Application\Command\RemoveEvidence\RemoveEvidenceHandler;
use App\Inventory\Application\Command\StartRepair\StartRepairCommand;
use App\Inventory\Application\Command\StartRepair\StartRepairHandler;
use App\Inventory\Application\Exception\ConsumableNotFoundException;
use App\Inventory\Application\Exception\LocationNotFoundException;
use App\Inventory\Application\Exception\RepairEvidenceNotFoundException;
use App\Inventory\Application\Exception\RepairNotFoundException;
use App\Inventory\Application\Query\FindRepairById\FindRepairByIdHandler;
use App\Inventory\Application\Query\ListRepairConsumables\ListRepairConsumablesQuery;
use App\Inventory\Application\Query\ListRepairConsumables\ListRepairConsumablesHandler;
use App\Inventory\Application\Query\ListRepairEvidences\ListRepairEvidencesQuery;
use App\Inventory\Application\Query\ListRepairEvidences\ListRepairEvidencesHandler;
use App\Inventory\Application\Query\ListRepairs\ListRepairsHandler;
use App\Shared\Exceptions\ApplicationException;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/inventory/repairs')]
final class ApiInventoryRepairController extends AbstractController
{
    #[IsGranted('inventory.view')]
    #[Route('', name: 'api_inventory_repairs_list', methods: ['GET'])]
    public function list(
        Request $request,
        ListRepairsHandler $handler,
    ): JsonResponse {
        $status           = $request->query->get('status');
        $serializedStockId = $request->query->get('serializedStockId');
        $laboratoryId     = $request->query->get('laboratoryId');

        $repairs = $handler($status !== null ? (string) $status : null);

        if ($serializedStockId !== null) {
            $repairs = array_values(array_filter(
                $repairs,
                static fn($r) => $r->serializedStockId === $serializedStockId,
            ));
        }

        if ($laboratoryId !== null) {
            $repairs = array_values(array_filter(
                $repairs,
                static fn($r) => $r->laboratoryId === $laboratoryId,
            ));
        }

        return $this->json(['data' => $repairs]);
    }

    #[IsGranted('inventory.create')]
    #[Route('', name: 'api_inventory_repairs_assign', methods: ['POST'])]
    public function assign(
        Request $request,
        AssignRepairHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            ($handler)(new AssignRepairCommand(
                serializedStockId: trim((string) ($payload['serializedStockId'] ?? '')),
                laboratoryId:      trim((string) ($payload['locationId'] ?? '')),
                assignedById:      trim((string) ($payload['assignedById'] ?? '')),
                notes:            isset($payload['notes']) ? trim((string) $payload['notes']) : null,
            ));

            return $this->json(
                ['data' => ['serializedStockId' => trim((string) ($payload['serializedStockId'] ?? ''))]],
                Response::HTTP_CREATED,
            );
        } catch (ApplicationException | DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.view')]
    #[Route('/{id}', name: 'api_inventory_repairs_show', methods: ['GET'])]
    public function show(
        string $id,
        FindRepairByIdHandler $handler,
    ): JsonResponse {
        try {
            $repair = $handler($id);

            return $this->json(['data' => $repair]);
        } catch (RepairNotFoundException) {
            return $this->json(['error' => 'Reparación no encontrada.'], Response::HTTP_NOT_FOUND);
        }
    }

    #[IsGranted('inventory.update')]
    #[Route('/{id}/start', name: 'api_inventory_repairs_start', methods: ['POST'])]
    public function start(
        string $id,
        Request $request,
        StartRepairHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            ($handler)(new StartRepairCommand(
                id:            $id,
                technicianId:  trim((string) ($payload['technicianId'] ?? '')),
            ));

            return $this->json(['data' => ['id' => $id, 'status' => 'IN_PROGRESS']]);
        } catch (RepairNotFoundException) {
            return $this->json(['error' => 'Reparación no encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.update')]
    #[Route('/{id}/complete', name: 'api_inventory_repairs_complete', methods: ['POST'])]
    public function complete(
        string $id,
        Request $request,
        CompleteRepairHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            ($handler)(new CompleteRepairCommand(
                id:               $id,
                completionNotes:  isset($payload['completionNotes']) ? trim((string) $payload['completionNotes']) : null,
            ));

            return $this->json(['data' => ['id' => $id, 'status' => 'COMPLETED']]);
        } catch (RepairNotFoundException) {
            return $this->json(['error' => 'Reparación no encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.update')]
    #[Route('/{id}/mark-unrepairable', name: 'api_inventory_repairs_mark_unrepairable', methods: ['POST'])]
    public function markUnrepairable(
        string $id,
        Request $request,
        MarkRepairUnrepairableHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            ($handler)(new MarkRepairUnrepairableCommand(
                id:    $id,
                notes: isset($payload['notes']) ? trim((string) $payload['notes']) : null,
            ));

            return $this->json(['data' => ['id' => $id, 'status' => 'UNREPAIRABLE']]);
        } catch (RepairNotFoundException) {
            return $this->json(['error' => 'Reparación no encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    // ─── Evidence ─────────────────────────────────────────────────────────

    #[IsGranted('inventory.update')]
    #[Route('/{repairId}/evidence', name: 'api_inventory_repairs_evidence_upload', methods: ['POST'])]
    public function uploadEvidence(
        string $repairId,
        Request $request,
        AttachEvidenceHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            $evidenceId = ($handler)(new AttachEvidenceCommand(
                repairId:   $repairId,
                filePath:   trim((string) ($payload['filePath'] ?? '')),
                type:       trim((string) ($payload['type'] ?? '')),
                uploadedBy: trim((string) ($payload['uploadedBy'] ?? '')),
                notes:     isset($payload['notes']) ? trim((string) $payload['notes']) : null,
            ));

            return $this->json(
                ['data' => ['id' => $evidenceId]],
                Response::HTTP_CREATED,
            );
        } catch (RepairNotFoundException) {
            return $this->json(['error' => 'Reparación no encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (ApplicationException | DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.view')]
    #[Route('/{repairId}/evidence', name: 'api_inventory_repairs_evidence_list', methods: ['GET'])]
    public function listEvidence(
        string $repairId,
        ListRepairEvidencesHandler $handler,
    ): JsonResponse {
        $evidences = ($handler)(new ListRepairEvidencesQuery(
            repairId: $repairId,
        ));

        return $this->json(['data' => $evidences]);
    }

    #[IsGranted('inventory.update')]
    #[Route('/{repairId}/evidence/{evidenceId}', name: 'api_inventory_repairs_evidence_delete', methods: ['DELETE'])]
    public function deleteEvidence(
        string $repairId,
        string $evidenceId,
        RemoveEvidenceHandler $handler,
    ): JsonResponse {
        try {
            ($handler)(new RemoveEvidenceCommand(
                repairId:   $repairId,
                evidenceId: $evidenceId,
            ));

            return $this->json(['data' => ['id' => $evidenceId, 'deleted' => true]]);
        } catch (RepairEvidenceNotFoundException) {
            return $this->json(['error' => 'Evidencia no encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    // ─── Consumables ──────────────────────────────────────────────────────

    #[IsGranted('inventory.update')]
    #[Route('/{repairId}/consumables', name: 'api_inventory_repairs_consumables_register', methods: ['POST'])]
    public function registerConsumable(
        string $repairId,
        Request $request,
        RegisterConsumableHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            $consumableId = ($handler)(new RegisterConsumableCommand(
                repairId:    $repairId,
                productId:   trim((string) ($payload['productId'] ?? '')),
                productCode: trim((string) ($payload['productCode'] ?? '')),
                productName: trim((string) ($payload['productName'] ?? '')),
                quantity:    (int) ($payload['quantity'] ?? 0),
                unitCost:   isset($payload['unitCost']) ? (float) $payload['unitCost'] : null,
                notes:      isset($payload['notes']) ? trim((string) $payload['notes']) : null,
            ));

            return $this->json(
                ['data' => ['id' => $consumableId]],
                Response::HTTP_CREATED,
            );
        } catch (RepairNotFoundException) {
            return $this->json(['error' => 'Reparación no encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (ApplicationException | DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.view')]
    #[Route('/{repairId}/consumables', name: 'api_inventory_repairs_consumables_list', methods: ['GET'])]
    public function listConsumables(
        string $repairId,
        ListRepairConsumablesHandler $handler,
    ): JsonResponse {
        $consumables = ($handler)(new ListRepairConsumablesQuery(
            repairId: $repairId,
        ));

        return $this->json(['data' => $consumables]);
    }

    #[IsGranted('inventory.update')]
    #[Route('/{repairId}/consumables/{consumableId}', name: 'api_inventory_repairs_consumables_delete', methods: ['DELETE'])]
    public function deleteConsumable(
        string $repairId,
        string $consumableId,
        RemoveConsumableHandler $handler,
    ): JsonResponse {
        try {
            ($handler)(new RemoveConsumableCommand(
                repairId:     $repairId,
                consumableId: $consumableId,
            ));

            return $this->json(['data' => ['id' => $consumableId, 'deleted' => true]]);
        } catch (ConsumableNotFoundException) {
            return $this->json(['error' => 'Consumible no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    // ─── Emergency Part Usage ─────────────────────────────────────────────

    #[IsGranted('inventory.update')]
    #[Route('/emergency-part-usage', name: 'api_inventory_repairs_emergency_part_usage', methods: ['POST'])]
    public function registerEmergencyPartUsage(
        Request $request,
        RegisterEmergencyPartUsageHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            ($handler)(new RegisterEmergencyPartUsageCommand(
                productCode:  trim((string) ($payload['productCode'] ?? '')),
                employeeId:   trim((string) ($payload['employeeId'] ?? '')),
                serialNumber: trim((string) ($payload['serialNumber'] ?? '')),
                changedBy:    trim((string) ($payload['changedBy'] ?? '')),
                notes:       isset($payload['notes']) ? trim((string) $payload['notes']) : null,
            ));

            return $this->json(['data' => ['status' => 'registered']], Response::HTTP_CREATED);
        } catch (\InvalidArgumentException | \LogicException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
