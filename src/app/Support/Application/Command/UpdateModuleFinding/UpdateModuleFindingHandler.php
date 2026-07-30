<?php

declare(strict_types=1);

namespace App\Support\Application\Command\UpdateModuleFinding;

use App\Shared\Exceptions\ApplicationException;
use App\Support\Domain\AtmModel\FindingType;
use App\Support\Domain\Repositories\TicketModuleFindingRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketModuleFindingIdValueObject;

final readonly class UpdateModuleFindingHandler
{
    public function __construct(
        private TicketModuleFindingRepositoryInterface $findingRepo,
    ) {}

    public function __invoke(UpdateModuleFindingCommand $command): void
    {
        $findingId = TicketModuleFindingIdValueObject::fromString($command->findingId);

        $finding = $this->findingRepo->findById($findingId);
        if ($finding === null) {
            throw new ApplicationException(
                sprintf('Module finding con ID "%s" no encontrado.', $command->findingId)
            );
        }

        $finding->changeFindingType(
            FindingType::from($command->findingType),
            $command->notes,
        );

        $this->findingRepo->save($finding);
    }
}
