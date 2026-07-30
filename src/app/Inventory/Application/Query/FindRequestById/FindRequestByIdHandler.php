<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\FindRequestById;

use App\Inventory\Application\Exception\RequestNotFoundException;
use App\Inventory\Application\Query\Response\RequestResponse;
use App\Inventory\Domain\Repositories\RequestRepositoryInterface;
use App\Inventory\Domain\ValueObjects\RequestIdValueObject;

final readonly class FindRequestByIdHandler
{
    public function __construct(
        private RequestRepositoryInterface $requestRepository,
    ) {
    }

    public function __invoke(string $id): RequestResponse
    {
        $request = $this->requestRepository->findByIdWithItems(new RequestIdValueObject($id));

        if ($request === null) {
            throw RequestNotFoundException::withId($id);
        }

        return RequestResponse::fromEntity($request);
    }
}
