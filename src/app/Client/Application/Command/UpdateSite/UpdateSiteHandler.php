<?php

declare(strict_types=1);

namespace App\Client\Application\Command\UpdateSite;

use App\Client\Application\Exception\SiteNotFoundException;
use App\Client\Domain\Repositories\SiteRepositoryInterface;
use App\Client\Domain\ValueObjects\SiteIdValueObject;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;

final readonly class UpdateSiteHandler
{
    public function __construct(
        private SiteRepositoryInterface $repository,
    ) {
    }

    public function __invoke(UpdateSiteCommand $command): void
    {
        $site = $this->repository->findById(new SiteIdValueObject($command->siteId))
            ?? throw SiteNotFoundException::withId($command->siteId);

        $site->changeName($command->name);

        if ($command->serviceZoneId !== null) {
            $site->assignToZone(new ServiceZoneIdValueObject($command->serviceZoneId));
        } else {
            $site->removeFromZone();
        }

        $this->repository->save($site);
    }
}
