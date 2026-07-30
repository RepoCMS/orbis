<?php

declare(strict_types=1);

namespace App\Inventory\Presentation\Http;

use App\Inventory\Application\Command\RecordTransformation\RecordTransformationCommand;
use App\Inventory\Application\Command\RecordTransformation\RecordTransformationHandler;
use App\Inventory\Application\Query\FindTransformationById\FindTransformationByIdHandler;
use App\Inventory\Application\Query\ListTransformations\ListTransformationsHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/inventory/transformations')]
final class ApiInventoryTransformationController extends AbstractController
{
    #[IsGranted('inventory.view')]
    #[Route('', name: 'api_inventory_transformations_list', methods: ['GET'])]
    public function list(
        ListTransformationsHandler $handler,
    ): JsonResponse {
        return $this->json(['data' => $handler()]);
    }

    #[IsGranted('inventory.create')]
    #[Route('', name: 'api_inventory_transformations_create', methods: ['POST'])]
    public function create(
        Request $request,
        RecordTransformationHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            ($handler)(new RecordTransformationCommand(
                serializedStockId: trim((string) ($payload['outgoingSerializedStockId'] ?? '')),
                laboratoryId:      trim((string) ($payload['locationId'] ?? '')),
                performedById:     trim((string) ($payload['performedById'] ?? '')),
                newProductId:      isset($payload['incomingProductId']) ? trim((string) $payload['incomingProductId']) : null,
                newSerialNumber:   isset($payload['incomingSerialNumber']) ? trim((string) $payload['incomingSerialNumber']) : null,
                notes:             isset($payload['notes']) ? trim((string) $payload['notes']) : null,
            ));

            return $this->json(
                ['data' => ['serializedStockId' => trim((string) ($payload['outgoingSerializedStockId'] ?? ''))]],
                Response::HTTP_CREATED,
            );
        } catch (\InvalidArgumentException | \LogicException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.view')]
    #[Route('/{id}', name: 'api_inventory_transformations_show', methods: ['GET'])]
    public function show(
        string $id,
        FindTransformationByIdHandler $handler,
    ): JsonResponse {
        try {
            $transformation = $handler($id);

            return $this->json(['data' => $transformation]);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => 'Transformación no encontrada.'], Response::HTTP_NOT_FOUND);
        }
    }
}
