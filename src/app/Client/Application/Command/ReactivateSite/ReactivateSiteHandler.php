<?php

declare(strict_types=1);

namespace App\Client\Application\Command\ReactivateSite;

use App\Client\Application\Exception\SiteNotFoundException;
use App\Client\Domain\Repositories\SiteRepositoryInterface;
use App\Client\Domain\ValueObjects\SiteIdValueObject;

final readonly class ReactivateSiteHandler
{
    public function __construct(
        private SiteRepositoryInterface $repository,
    ) {
    }

    public function __invoke(ReactivateSiteCommand $command): void
    {
        $site = $this->repository->findById(new SiteIdValueObject($command->siteId))
            ?? throw SiteNotFoundException::withId($command->siteId);

        $site->reactivate();

        $this->repository->save($site);
    }
}
