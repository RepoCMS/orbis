<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\ListEntries;

use App\Inventory\Application\Query\Response\EntryResponse;
use App\Inventory\Domain\Repositories\EntryRepositoryInterface;

final readonly class ListEntriesHandler
{
    public function __construct(
        private EntryRepositoryInterface $entryRepository,
    ) {
    }

    /** @return EntryResponse[] */
    public function __invoke(): array
    {
        return array_map(
            fn ($e) => EntryResponse::fromEntity($e),
            $this->entryRepository->findAll(),
        );
    }
}
