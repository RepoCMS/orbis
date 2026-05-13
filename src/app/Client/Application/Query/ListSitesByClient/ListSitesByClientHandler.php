<?php

declare(strict_types=1);

namespace App\Client\Application\Query\ListSitesByClient;

use App\Client\Application\Query\Response\SiteResponse;
use App\Client\Domain\Repositories\SiteRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;

final readonly class ListSitesByClientHandler
{
    public function __construct(
        private SiteRepositoryInterface $repository,
    ) {
    }

    /**
     * @return SiteResponse[]
     */
    public function __invoke(ListSitesByClientQuery $query): array
    {
        $sites = $this->repository->findByClientId(new ClientIdValueObject($query->clientId));

        return array_map(SiteResponse::fromEntity(...), $sites);
    }
}
