<?php

declare(strict_types=1);

namespace App\Client\Application\Command\AddSite;

use App\Client\Domain\Entities\Site;
use App\Client\Domain\Repositories\SiteRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\SiteIdValueObject;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class AddSiteHandler
{
    public function __construct(
        private SiteRepositoryInterface $repository,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(AddSiteCommand $command): string
    {
        $id = $this->idGenerator->generate();

        $serviceZoneId = $command->serviceZoneId !== null
            ? new ServiceZoneIdValueObject($command->serviceZoneId)
            : null;

        $site = Site::add(
            id:            new SiteIdValueObject($id),
            clientId:      new ClientIdValueObject($command->clientId),
            cityId:        $command->cityId,
            terminalId:    new TerminalIdValueObject($command->terminalId),
            name:          $command->name,
            serviceZoneId: $serviceZoneId,
        );

        $this->repository->save($site);

        return $id;
    }
}
