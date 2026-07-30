<?php

declare(strict_types=1);

namespace App\Inventory\Presentation\Http;

use App\Inventory\Application\Query\InventoryDashboardMetrics\InventoryDashboardMetricsHandler;
use App\Inventory\Application\Query\ListEntries\ListEntriesHandler;
use App\Inventory\Application\Query\ListLocations\ListLocationsHandler;
use App\Inventory\Application\Query\ListProducts\ListProductsHandler;
use App\Inventory\Application\Query\ListPurchaseOrders\ListPurchaseOrdersHandler;
use App\Inventory\Application\Query\ListPurchaseOrders\ListPurchaseOrdersQuery;
use App\Inventory\Application\Query\ListRepairs\ListRepairsHandler;
use App\Inventory\Application\Query\ListStockAlerts\ListStockAlertsHandler;
use App\Inventory\Application\Query\ListStockAlerts\ListStockAlertsQuery;
use App\Inventory\Application\Query\StockOverview\StockOverviewHandler;
use App\Inventory\Application\Query\StockOverview\StockOverviewQuery;
use App\Inventory\Application\Service\StockViewScopeResolver;
use App\Inventory\Domain\Repositories\RepairRepositoryInterface;
use App\Inventory\Domain\Repositories\SerialHistoryRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\TransformationRepositoryInterface;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Identity\Infrastructure\Security\SymfonySecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/inventory')]
final class ApiInventoryStockController extends AbstractController
{
    #[IsGranted('inventory.view')]
    #[Route('/stock', name: 'api_inventory_stock_overview', methods: ['GET'])]
    public function stockOverview(
        Request                $request,
        StockOverviewHandler   $handler,
        ListLocationsHandler   $listLocations,
        ListProductsHandler    $listProducts,
        StockViewScopeResolver $scopeResolver,
    ): JsonResponse {
        $user       = $this->getUser();
        $userId     = $user instanceof SymfonySecurityUser ? (string) $user->getDomainUser()->id() : '';
        $scopeLocId = $userId !== '' ? $scopeResolver->resolve($userId) : null;

        $locationId = $scopeLocId ?? ($request->query->get('location') ?: null);
        $productId  = $request->query->get('product') ?: null;
        $status     = $request->query->get('status') ?: null;
        $type       = $request->query->get('type') ?: null;
        $search     = $request->query->get('q') ?: null;
        $page       = max(1, (int) $request->query->get('page', '1'));

        $query = new StockOverviewQuery(
            locationId: $locationId,
            productId:  $productId,
            status:     $status,
            type:       $type,
            search:     $search,
            page:       $page,
        );

        $result    = $handler($query);
        $locations = $listLocations();
        $products  = $listProducts();

        $serialized    = [];
        $nonserialized = [];

        foreach ($result->items as $item) {
            if ($item->type === 'serialized') {
                $serialized[] = $item;
            } else {
                $nonserialized[] = $item;
            }
        }

        return $this->json([
            'data' => [
                'serialized'    => $serialized,
                'nonserialized' => $nonserialized,
                'pagination'    => [
                    'page'       => $result->page,
                    'perPage'    => $result->perPage,
                    'total'      => $result->total,
                    'totalPages' => $result->totalPages(),
                ],
                'totals' => [
                    'available'      => $result->totalAvailable,
                    'damaged'        => $result->totalDamaged,
                    'reserved'       => $result->totalReserved,
                    'decommissioned' => $result->totalDecommissioned,
                ],
            ],
            'filters'  => [
                'locations' => $locations,
                'products'  => $products,
            ],
        ]);
    }

    #[IsGranted('inventory.view')]
    #[Route('/serial-history/{serializedStockId}', name: 'api_inventory_serial_history', methods: ['GET'])]
    public function serialHistory(
        string $serializedStockId,
        SerialHistoryRepositoryInterface $repository,
    ): JsonResponse {
        $history = $repository->findBySerializedStock(
            new SerializedStockIdValueObject($serializedStockId),
        );

        $data = array_map(
            static fn($h) => [
                'id'                  => (string) $h->id(),
                'serializedStockId'   => (string) $h->serializedStockId(),
                'previousSerialNumber' => (string) $h->previousSerialNumber(),
                'newSerialNumber'     => (string) $h->newSerialNumber(),
                'reason'              => $h->reason()->value,
                'changedAt'           => $h->changedAt()->format('Y-m-d H:i:s'),
            ],
            $history,
        );

        return $this->json(['data' => $data]);
    }

    #[IsGranted('inventory.view')]
    #[Route('/dashboard/summary', name: 'api_inventory_dashboard_summary', methods: ['GET'])]
    public function dashboardSummary(
        ListProductsHandler $listProducts,
        StockOverviewHandler $stockOverview,
        ListStockAlertsHandler $listAlerts,
        ListPurchaseOrdersHandler $listPurchaseOrders,
        ListRepairsHandler $listRepairs,
        InventoryDashboardMetricsHandler $dashboardMetrics,
    ): JsonResponse {
        $products       = $listProducts();
        $stockResult    = $stockOverview(new StockOverviewQuery());
        $alerts         = $listAlerts(new ListStockAlertsQuery());
        $purchaseOrders = $listPurchaseOrders(new ListPurchaseOrdersQuery());
        $repairs        = $listRepairs();
        $metrics        = $dashboardMetrics();

        $pendingOrders = array_values(array_filter(
            $purchaseOrders,
            static fn($po) => $po->status === 'open' || $po->status === 'partial',
        ));

        return $this->json([
            'data' => [
                'totalProducts'          => count($products),
                'totalStock'             => $stockResult->total,
                'availableStock'         => $stockResult->totalAvailable,
                'damagedStock'           => $stockResult->totalDamaged,
                'reservedStock'          => $stockResult->totalReserved,
                'decommissionedStock'    => $stockResult->totalDecommissioned,
                'stockAlerts'            => count($alerts),
                'pendingOrders'          => count($pendingOrders),
                'openRepairs'            => count($repairs),
                'totalInventoryValue'    => $metrics->totalInventoryValue,
                'deadStockCount'         => $metrics->deadStockCount,
                'itemsWithoutMovement90d' => $metrics->itemsWithoutMovement90d,
                'fillRatePct'            => $metrics->fillRatePct,
                'fillRate'               => $metrics->fillRate,
                'pendingRequests'        => $metrics->pendingRequests,
                'abcAnalysis'            => $metrics->abcAnalysis,
                'deadStock90d'           => $metrics->deadStock90d,
                'valueByCategory'        => $metrics->valueByCategory,
                'movementsByType'        => $metrics->movementsByType,
            ],
        ]);
    }

    #[IsGranted('inventory.view')]
    #[Route('/dashboard/pending-reconciliation', name: 'api_inventory_dashboard_pending_reconciliation', methods: ['GET'])]
    public function pendingReconciliation(
        ListEntriesHandler $listEntries,
    ): JsonResponse {
        $entries = $listEntries();

        $draft = array_values(array_filter(
            $entries,
            static fn($e) => $e->status === 'draft',
        ));

        return $this->json([
            'data' => [
                'totalEntries'   => count($entries),
                'draftEntries'   => count($draft),
                'entries'        => $draft,
            ],
        ]);
    }

    #[IsGranted('inventory.view')]
    #[Route('/stock/{id}/origin', name: 'api_inventory_stock_origin', methods: ['GET'])]
    public function stockOrigin(
        string $id,
        SerializedStockRepositoryInterface $serializedStockRepository,
        SerialHistoryRepositoryInterface $serialHistoryRepository,
        RepairRepositoryInterface $repairRepository,
        TransformationRepositoryInterface $transformationRepository,
    ): JsonResponse {
        $stockId = new SerializedStockIdValueObject($id);
        $stock   = $serializedStockRepository->findById($stockId);

        if ($stock === null) {
            return $this->json(['error' => 'Stock serializado no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        // Serial history
        $history = array_map(
            static fn($h) => [
                'id'             => (string) $h->id(),
                'previousSerial' => (string) $h->previousSerialNumber(),
                'newSerial'      => (string) $h->newSerialNumber(),
                'reason'         => $h->reason()->value,
                'changedAt'      => $h->changedAt()->format('Y-m-d H:i:s'),
            ],
            $serialHistoryRepository->findBySerializedStock($stockId),
        );

        // Repairs on this stock
        $repairs = array_filter(
            iterator_to_array($repairRepository->findBySerializedStock($stockId)),
            static fn($r) => true,
        );

        // Transformations
        $transformations = array_filter(
            $transformationRepository->findBySerializedStock($stockId),
            static fn($t) => true,
        );

        return $this->json([
            'data' => [
                'stock' => [
                    'id'               => (string) $stock->id(),
                    'productId'        => (string) $stock->productId(),
                    'serialNumber'     => (string) $stock->serialNumber(),
                    'status'           => $stock->status()->value,
                    'locationId'       => (string) $stock->locationId(),
                    'createdAt'        => $stock->createdAt()->format('Y-m-d H:i:s'),
                    'originTicketId'   => $stock->originTicketId(),
                    'originClientId'   => $stock->originClientId(),
                    'originTerminalId' => $stock->originTerminalId(),
                    'isVandalism'      => $stock->isVandalism(),
                    'repairCount'      => $stock->repairCount(),
                ],
                'serialHistory'  => $history,
                'repairs'        => $repairs,
                'transformations' => $transformations,
            ],
        ]);
    }
}
