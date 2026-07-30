<?php

declare(strict_types=1);

namespace App\Inventory\Presentation\Http;

use App\Inventory\Application\Command\CreateLocation\CreateLocationCommand;
use App\Inventory\Application\Command\CreateLocation\CreateLocationHandler;
use App\Inventory\Application\Command\CreateSubLocation\CreateSubLocationCommand;
use App\Inventory\Application\Command\CreateSubLocation\CreateSubLocationHandler;
use App\Inventory\Application\Command\DefineStockMinimum\DefineStockMinimumCommand;
use App\Inventory\Application\Command\DefineStockMinimum\DefineStockMinimumHandler;
use App\Inventory\Application\Command\DeleteLocation\DeleteLocationCommand;
use App\Inventory\Application\Command\DeleteLocation\DeleteLocationHandler;
use App\Inventory\Application\Command\DeleteStockMinimum\DeleteStockMinimumCommand;
use App\Inventory\Application\Command\DeleteStockMinimum\DeleteStockMinimumHandler;
use App\Inventory\Application\Command\DeleteSubLocation\DeleteSubLocationCommand;
use App\Inventory\Application\Command\DeleteSubLocation\DeleteSubLocationHandler;
use App\Inventory\Application\Command\UpdateLocation\UpdateLocationCommand;
use App\Inventory\Application\Command\UpdateLocation\UpdateLocationHandler;
use App\Inventory\Application\Command\UpdateSubLocation\UpdateSubLocationCommand;
use App\Inventory\Application\Command\UpdateSubLocation\UpdateSubLocationHandler;
use App\Inventory\Application\Exception\LocationNotFoundException;
use App\Inventory\Application\Exception\SubLocationNotFoundException;
use App\Inventory\Application\Query\FindLocationById\FindLocationByIdHandler;
use App\Inventory\Application\Query\ListLocations\ListLocationsHandler;
use App\Inventory\Application\Query\ListStockAlerts\ListStockAlertsHandler;
use App\Inventory\Application\Query\ListStockAlerts\ListStockAlertsQuery;
use App\Inventory\Application\Query\ListStockMinimums\ListStockMinimumsHandler;
use App\Inventory\Application\Query\ListStockMinimums\ListStockMinimumsQuery;
use App\Inventory\Application\Query\ListSubLocationsByLocation\ListSubLocationsByLocationHandler;
use App\Inventory\Application\Query\ListSubLocationsByLocation\ListSubLocationsByLocationQuery;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/inventory')]
final class ApiInventoryLocationController extends AbstractController
{
    #[IsGranted('inventory.view')]
    #[Route('/locations', name: 'api_inventory_locations_list', methods: ['GET'])]
    public function listLocations(
        Request $request,
        ListLocationsHandler $handler,
    ): JsonResponse {
        $locations = $handler();

        $type = $request->query->get('type');
        $city = $request->query->get('city');

        if ($type !== null) {
            $locations = array_values(array_filter(
                $locations,
                static fn($l) => $l->type === $type,
            ));
        }

        if ($city !== null) {
            $locations = array_values(array_filter(
                $locations,
                static fn($l) => mb_strtolower($l->city) === mb_strtolower($city),
            ));
        }

        return $this->json(['data' => $locations]);
    }

    #[IsGranted('inventory.create')]
    #[Route('/locations', name: 'api_inventory_locations_create', methods: ['POST'])]
    public function createLocation(
        Request $request,
        CreateLocationHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            ($handler)(new CreateLocationCommand(
                name:    trim((string) ($payload['name'] ?? '')),
                city:    trim((string) ($payload['city'] ?? '')),
                address: trim((string) ($payload['address'] ?? '')),
                type:    trim((string) ($payload['type'] ?? '')),
            ));

            return $this->json(['data' => ['name' => trim((string) ($payload['name'] ?? ''))]], Response::HTTP_CREATED);
        } catch (DomainException | \InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.view')]
    #[Route('/locations/{id}', name: 'api_inventory_locations_show', methods: ['GET'])]
    public function showLocation(
        string $id,
        FindLocationByIdHandler $handler,
    ): JsonResponse {
        try {
            $location = $handler($id);

            return $this->json(['data' => $location]);
        } catch (LocationNotFoundException) {
            return $this->json(['error' => 'Ubicación no encontrada.'], Response::HTTP_NOT_FOUND);
        }
    }

    #[IsGranted('inventory.update')]
    #[Route('/locations/{id}', name: 'api_inventory_locations_update', methods: ['PATCH'])]
    public function updateLocation(
        string $id,
        Request $request,
        UpdateLocationHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            ($handler)(new UpdateLocationCommand(
                id:      $id,
                name:    trim((string) ($payload['name'] ?? '')),
                city:    trim((string) ($payload['city'] ?? '')),
                address: trim((string) ($payload['address'] ?? '')),
            ));

            return $this->json(['data' => ['id' => $id]]);
        } catch (LocationNotFoundException) {
            return $this->json(['error' => 'Ubicación no encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.delete')]
    #[Route('/locations/{id}', name: 'api_inventory_locations_delete', methods: ['DELETE'])]
    public function deleteLocation(
        string $id,
        DeleteLocationHandler $handler,
    ): JsonResponse {
        try {
            ($handler)(new DeleteLocationCommand($id));

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (LocationNotFoundException) {
            return $this->json(['error' => 'Ubicación no encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.view')]
    #[Route('/stock-minimums', name: 'api_inventory_stock_minimums_list', methods: ['GET'])]
    public function listStockMinimums(
        Request $request,
        ListStockMinimumsHandler $handler,
    ): JsonResponse {
        $locationId = $request->query->get('locationId');
        $page       = (int) ($request->query->get('page', '1'));
        $perPage    = (int) ($request->query->get('perPage', '20'));

        $result = $handler(new ListStockMinimumsQuery(
            locationId: $locationId !== null ? (string) $locationId : null,
            page:       $page,
            perPage:    $perPage,
        ));

        return $this->json([
            'data' => $result->items,
            'meta' => [
                'total'      => $result->total,
                'page'       => $result->page,
                'perPage'    => $result->perPage,
                'totalPages' => $result->totalPages(),
            ],
        ]);
    }

    #[IsGranted('inventory.create')]
    #[Route('/stock-minimums', name: 'api_inventory_stock_minimums_create', methods: ['POST'])]
    public function defineStockMinimum(
        Request $request,
        DefineStockMinimumHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            ($handler)(new DefineStockMinimumCommand(
                productId:      trim((string) ($payload['productId'] ?? '')),
                locationId:     trim((string) ($payload['locationId'] ?? '')),
                idealQuantity:  (int) ($payload['idealQuantity'] ?? 0),
                alertThreshold: (int) ($payload['alertThreshold'] ?? 0),
            ));

            return $this->json(['data' => ['status' => 'defined']], Response::HTTP_CREATED);
        } catch (\LogicException | DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.update')]
    #[Route('/stock-minimums/{id}', name: 'api_inventory_stock_minimums_update', methods: ['PATCH'])]
    public function updateStockMinimum(
        string $id,
        Request $request,
        DefineStockMinimumHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            ($handler)(new DefineStockMinimumCommand(
                productId:      trim((string) ($payload['productId'] ?? '')),
                locationId:     trim((string) ($payload['locationId'] ?? '')),
                idealQuantity:  (int) ($payload['idealQuantity'] ?? 0),
                alertThreshold: (int) ($payload['alertThreshold'] ?? 0),
            ));

            return $this->json(['data' => ['id' => $id]]);
        } catch (\LogicException | DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.delete')]
    #[Route('/stock-minimums/{id}', name: 'api_inventory_stock_minimums_delete', methods: ['DELETE'])]
    public function deleteStockMinimum(
        string $id,
        DeleteStockMinimumHandler $handler,
    ): JsonResponse {
        try {
            ($handler)(new DeleteStockMinimumCommand($id));

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (\LogicException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[IsGranted('inventory.view')]
    #[Route('/stock-alerts', name: 'api_inventory_stock_alerts_list', methods: ['GET'])]
    public function listStockAlerts(
        Request $request,
        ListStockAlertsHandler $handler,
    ): JsonResponse {
        $locationId = $request->query->get('locationId');

        $alerts = $handler(new ListStockAlertsQuery(
            locationId: $locationId !== null ? (string) $locationId : null,
        ));

        return $this->json(['data' => $alerts]);
    }

    // ─── SubLocations ─────────────────────────────────────────────────────────

    #[IsGranted('inventory.create')]
    #[Route('/locations/{locationId}/sub-locations', name: 'api_inventory_sub_locations_create', methods: ['POST'])]
    public function createSubLocation(
        string $locationId,
        Request $request,
        CreateSubLocationHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            ($handler)(new CreateSubLocationCommand(
                locationId: $locationId,
                type:       trim((string) ($payload['type'] ?? '')),
                name:       trim((string) ($payload['name'] ?? '')),
                code:       trim((string) ($payload['code'] ?? '')),
                parentId:   isset($payload['parentId']) ? trim((string) $payload['parentId']) : null,
            ));

            return $this->json(['data' => ['status' => 'created']], Response::HTTP_CREATED);
        } catch (\InvalidArgumentException | DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.view')]
    #[Route('/locations/{locationId}/sub-locations', name: 'api_inventory_sub_locations_list', methods: ['GET'])]
    public function listSubLocations(
        string $locationId,
        ListSubLocationsByLocationHandler $handler,
    ): JsonResponse {
        $tree = $handler(new ListSubLocationsByLocationQuery($locationId));

        return $this->json(['data' => $tree]);
    }

    #[IsGranted('inventory.update')]
    #[Route('/sub-locations/{id}', name: 'api_inventory_sub_locations_update', methods: ['PATCH'])]
    public function updateSubLocation(
        string $id,
        Request $request,
        UpdateSubLocationHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            ($handler)(new UpdateSubLocationCommand(
                id:   $id,
                name: trim((string) ($payload['name'] ?? '')),
                code: trim((string) ($payload['code'] ?? '')),
            ));

            return $this->json(['data' => ['id' => $id]]);
        } catch (SubLocationNotFoundException) {
            return $this->json(['error' => 'Sub-ubicación no encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('inventory.delete')]
    #[Route('/sub-locations/{id}', name: 'api_inventory_sub_locations_delete', methods: ['DELETE'])]
    public function deleteSubLocation(
        string $id,
        DeleteSubLocationHandler $handler,
    ): JsonResponse {
        try {
            ($handler)(new DeleteSubLocationCommand($id));

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (SubLocationNotFoundException) {
            return $this->json(['error' => 'Sub-ubicación no encontrada.'], Response::HTTP_NOT_FOUND);
        }
    }
}
