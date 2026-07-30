<?php

declare(strict_types=1);

namespace App\Client\Application\Query\FindWarrantiesByTerminal;

use App\Client\Application\Query\Response\WarrantyResponse;
use App\Client\Domain\Repositories\TerminalWarrantyRepositoryInterface;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;

final readonly class FindWarrantiesByTerminalHandler
{
    public function __construct(
        private TerminalWarrantyRepositoryInterface $warrantyRepository,
    ) {
    }

    /**
     * @return WarrantyResponse[]
     */
    public function __invoke(FindWarrantiesByTerminalQuery $query): array
    {
        return array_map(
            static fn($warranty) => WarrantyResponse::fromEntity($warranty),
            $this->warrantyRepository->findByTerminalId(new TerminalIdValueObject($query->terminalId)),
        );
    }
}
