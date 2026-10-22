<?php

declare(strict_types=1);

namespace App\Client\Application\Command\UpdateWarranty;

use App\Client\Application\Exception\TerminalWarrantyNotFoundException;
use App\Client\Domain\Enums\WarrantyType;
use App\Client\Domain\Events\WarrantyUpdated;
use App\Client\Domain\Repositories\TerminalRepositoryInterface;
use App\Client\Domain\Repositories\TerminalWarrantyRepositoryInterface;
use App\Client\Domain\ValueObjects\TerminalWarrantyIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use DateTimeImmutable;

final readonly class UpdateWarrantyHandler
{
    public function __construct(
        private TerminalWarrantyRepositoryInterface $warrantyRepository,
        private TerminalRepositoryInterface $terminalRepository,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(UpdateWarrantyCommand $command): void
    {
        $warrantyId = new TerminalWarrantyIdValueObject($command->warrantyId);

        $warranty = $this->warrantyRepository->findById($warrantyId)
            ?? throw TerminalWarrantyNotFoundException::withId($command->warrantyId);

        if ($command->type !== null) {
            $warranty->changeType(WarrantyType::from($command->type));
        }

        if ($command->endDate !== null) {
            $warranty->extendEndDate(new DateTimeImmutable($command->endDate));
        }

        if ($command->terms !== null) {
            $warranty->updateTerms($command->terms);
        }

        $this->warrantyRepository->save($warranty);

        $terminal = $this->terminalRepository->findById($warranty->terminalId());

        $this->eventPublisher->publish(new WarrantyUpdated(
            warrantyId: $command->warrantyId,
            terminalId: (string) $warranty->terminalId(),
            clientId:   (string) $terminal->clientId(),
        ));
    }
}
