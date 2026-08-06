<?php

declare(strict_types=1);

namespace App\Support\Application\Query\ListQuickTemplatesByModel;

use App\Inventory\Domain\Entities\NonserializedStock;
use App\Inventory\Domain\Repositories\NonserializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Support\Application\Query\Response\QuickTemplateItemResponse;
use App\Support\Application\Query\Response\QuickTemplateResponse;
use App\Support\Domain\Repositories\QuickTemplateRepositoryInterface;

final readonly class ListQuickTemplatesByModelHandler
{
    public function __construct(
        private QuickTemplateRepositoryInterface $templateRepo,
        private ProductRepositoryInterface $productRepo,
        private SerializedStockRepositoryInterface $serializedStockRepo,
        private NonserializedStockRepositoryInterface $nonserializedStockRepo,
    ) {
    }

    /** @return QuickTemplateResponse[] */
    public function __invoke(ListQuickTemplatesByModelQuery $query): array
    {
        if ($query->onlyActive) {
            $templates = $this->templateRepo->findActiveByModelId($query->modelId);
        } else {
            $templates = $this->templateRepo->findByModelId($query->modelId);
        }

        return array_map(
            fn ($template) => $this->enrichWithStock(QuickTemplateResponse::fromEntity($template)),
            $templates,
        );
    }

    private function enrichWithStock(QuickTemplateResponse $response): QuickTemplateResponse
    {
        $enrichedItems = array_map(
            fn (QuickTemplateItemResponse $item) => $this->enrichItemWithStock($item),
            $response->items,
        );

        return new QuickTemplateResponse(
            id:          $response->id,
            modelId:     $response->modelId,
            name:        $response->name,
            description: $response->description,
            isActive:    $response->isActive,
            createdBy:   $response->createdBy,
            createdAt:   $response->createdAt,
            updatedAt:   $response->updatedAt,
            items:       $enrichedItems,
        );
    }

    private function enrichItemWithStock(QuickTemplateItemResponse $item): QuickTemplateItemResponse
    {
        if ($item->productCode === null) {
            return $item;
        }

        $product = $this->productRepo->findByCode(
            ProductCodeAssignedBySupplierValueObject::fromString($item->productCode),
        );

        if ($product === null) {
            return $item;
        }

        $stockItems = $product->isSerialized()
            ? $this->serializedStockRepo->findByProduct($product->id())
            : $this->nonserializedStockRepo->findByProduct($product->id());

        $availableStock = array_reduce(
            $stockItems,
            fn (int $carry, $stock): int => $carry + (
                $stock->isAvailable()
                    ? ($stock instanceof NonserializedStock ? $stock->quantity() : 1)
                    : 0
            ),
            0,
        );

        return new QuickTemplateItemResponse(
            id:             $item->id,
            modelPartId:    $item->modelPartId,
            partNumber:     $item->partNumber,
            productCode:    $item->productCode,
            description:    $item->description,
            quantity:       $item->quantity,
            notes:          $item->notes,
            sortOrder:      $item->sortOrder,
            createdAt:      $item->createdAt,
            availableStock: $availableStock,
        );
    }
}
