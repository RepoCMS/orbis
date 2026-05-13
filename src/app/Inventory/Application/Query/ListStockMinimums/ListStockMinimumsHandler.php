<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\ListStockMinimums;

use App\Inventory\Application\Query\Response\StockMinimumResponse;
use App\Inventory\Domain\Repositories\StockMinimumRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Shared\Application\Query\PaginatedResult;

final readonly class ListStockMinimumsHandler
{
    public function __construct(
        private StockMinimumRepositoryInterface $stockMinimumRepository,
    ) {
    }

    /** @return PaginatedResult<StockMinimumResponse> */
    public function __invoke(ListStockMinimumsQuery $query): PaginatedResult
    {
        $all = $query->locationId !== null
            ? $this->stockMinimumRepository->findByLocation(new LocationIdValueObject($query->locationId))
            : $this->stockMinimumRepository->findAll();

        $total  = count($all);
        $offset = ($query->page - 1) * $query->perPage;
        $paged  = array_slice($all, $offset, $query->perPage);

        return new PaginatedResult(
            items:   array_map(fn ($m) => StockMinimumResponse::fromEntity($m), $paged),
            total:   $total,
            page:    $query->page,
            perPage: $query->perPage,
        );
    }
}
