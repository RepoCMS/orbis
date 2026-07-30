<?php

declare(strict_types=1);

namespace App\Client\Application\Command\RegisterWarranty;

use App\Client\Application\Exception\TerminalNotFoundException;
use App\Client\Domain\Enums\WarrantyType;
use App\Client\Domain\Events\WarrantyRegistered;
use App\Client\Domain\Repositories\TerminalRepositoryInterface;
use App\Client\Domain\Repositories\TerminalWarrantyRepositoryInterface;
use App\Client\Domain\Entities\TerminalWarranty;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Client\Domain\ValueObjects\TerminalWarrantyIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use DateTimeImmutable;

final readonly class RegisterWarrantyHandler
{
    public function __construct(
        private TerminalRepositoryInterface $terminalRepository,
        private TerminalWarrantyRepositoryInterface $warrantyRepository,
        private IdGeneratorInterface $idGenerator,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(RegisterWarrantyCommand $command): void
    {
        $terminalId = new TerminalIdValueObject($command->terminalId);

        $terminal = $this->terminalRepository->findById($terminalId);
        if ($terminal === null) {
            throw TerminalNotFoundException::withId($command->terminalId);
        }

        $warranty = TerminalWarranty::register(
            id: new TerminalWarrantyIdValueObject($this->idGenerator->generate()),
            terminalId: $terminalId,
            type: WarrantyType::from($command->type),
            startDate: new DateTimeImmutable($command->startDate),
            endDate: new DateTimeImmutable($command->endDate),
            terms: $command->terms,
        );

        $this->warrantyRepository->save($warranty);

        $this->eventPublisher->publish(new WarrantyRegistered(
            warrantyId: (string) $warranty->id(),
            terminalId: $command->terminalId,
            type: $command->type,
        ));
    }
}
