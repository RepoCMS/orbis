<?php

declare(strict_types=1);

namespace App\Client\Application\Query\FindSiteById;

use App\Client\Application\Exception\SiteNotFoundException;
use App\Client\Application\Query\Response\SiteResponse;
use App\Client\Domain\Repositories\SiteRepositoryInterface;
use App\Client\Domain\ValueObjects\SiteIdValueObject;

final readonly class FindSiteByIdHandler
{
    public function __construct(
        private SiteRepositoryInterface $repository,
    ) {
    }

    public function __invoke(FindSiteByIdQuery $query): SiteResponse
    {
        $site = $this->repository->findById(new SiteIdValueObject($query->siteId))
            ?? throw SiteNotFoundException::withId($query->siteId);

        return SiteResponse::fromEntity($site);
    }
}
