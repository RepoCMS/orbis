<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\ListRequests;

use App\Inventory\Application\Query\Response\RequestResponse;
use App\Inventory\Domain\Repositories\RequestRepositoryInterface;

final readonly class ListRequestsHandler
{
    public function __construct(
        private RequestRepositoryInterface $requestRepository,
    ) {
    }

    /** @return RequestResponse[] */
    public function __invoke(?string $status = null): array
    {
        $requests = $status === 'pending'
            ? $this->requestRepository->findPending()
            : $this->requestRepository->findAll();

        return array_map(fn ($r) => RequestResponse::fromEntity($r), $requests);
    }
}
