<?php

declare(strict_types=1);

namespace App\Inventory\Presentation\Http;

use App\Inventory\Application\Query\ListLocations\ListLocationsHandler;
use App\Inventory\Application\Query\ListProducts\ListProductsHandler;
use App\Inventory\Application\Query\StockOverview\StockOverviewHandler;
use App\Inventory\Application\Query\StockOverview\StockOverviewQuery;
use App\Inventory\Domain\Enums\SerializedStockStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/inventory/stock')]
final class StockController extends AbstractController
{
    #[IsGranted('inventory.view')]
    #[Route('', name: 'inventory_stock_overview', methods: ['GET'])]
    public function overview(
        Request              $request,
        StockOverviewHandler $handler,
        ListLocationsHandler $listLocations,
        ListProductsHandler  $listProducts,
    ): Response {
        $locationId = $request->query->get('location') ?: null;
        $productId  = $request->query->get('product') ?: null;
        $status     = $request->query->get('status') ?: null;
        $type       = $request->query->get('type') ?: null;
        $search     = $request->query->get('search') ?: null;
        $page       = max(1, (int) $request->query->get('page', '1'));

        $query  = new StockOverviewQuery(
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
        $statuses  = SerializedStockStatus::cases();

        return $this->render('inventory/stock/overview.html.twig', [
            'result'    => $result,
            'locations' => $locations,
            'products'  => $products,
            'statuses'  => $statuses,
            'filters'   => compact('locationId', 'productId', 'status', 'type', 'search', 'page'),
        ]);
    }
}
