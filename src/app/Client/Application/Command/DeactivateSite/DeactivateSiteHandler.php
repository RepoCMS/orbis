<?php

declare(strict_types=1);

namespace App\Client\Application\Command\DeactivateSite;

use App\Client\Application\Exception\SiteNotFoundException;
use App\Client\Domain\Repositories\SiteRepositoryInterface;
use App\Client\Domain\ValueObjects\SiteIdValueObject;

final readonly class DeactivateSiteHandler
{
    public function __construct(
        private SiteRepositoryInterface $repository,
    ) {
    }

    public function __invoke(DeactivateSiteCommand $command): void
    {
        $site = $this->repository->findById(new SiteIdValueObject($command->siteId))
            ?? throw SiteNotFoundException::withId($command->siteId);

        $site->deactivate();

        $this->repository->save($site);
    }
}
