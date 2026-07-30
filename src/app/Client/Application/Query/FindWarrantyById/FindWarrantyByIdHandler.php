<?php

declare(strict_types=1);

namespace App\Client\Application\Query\FindWarrantyById;

use App\Client\Application\Exception\TerminalWarrantyNotFoundException;
use App\Client\Application\Query\Response\WarrantyResponse;
use App\Client\Domain\Repositories\TerminalWarrantyRepositoryInterface;
use App\Client\Domain\ValueObjects\TerminalWarrantyIdValueObject;

final readonly class FindWarrantyByIdHandler
{
    public function __construct(
        private TerminalWarrantyRepositoryInterface $warrantyRepository,
    ) {
    }

    public function __invoke(FindWarrantyByIdQuery $query): WarrantyResponse
    {
        $warranty = $this->warrantyRepository->findById(new TerminalWarrantyIdValueObject($query->warrantyId))
            ?? throw TerminalWarrantyNotFoundException::withId($query->warrantyId);

        return WarrantyResponse::fromEntity($warranty);
    }
}
