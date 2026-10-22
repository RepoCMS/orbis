<?php

declare(strict_types=1);

namespace App\Client\Application\Command\DeactivateTerminal;

use App\Client\Application\Exception\TerminalNotFoundException;
use App\Client\Domain\Events\TerminalDeactivated;
use App\Client\Domain\Repositories\TerminalRepositoryInterface;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

final readonly class DeactivateTerminalHandler
{
    public function __construct(
        private TerminalRepositoryInterface $terminalRepository,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(DeactivateTerminalCommand $command): void
    {
        $terminalId = new TerminalIdValueObject($command->terminalId);
        $terminal   = $this->terminalRepository->findById($terminalId)
            ?? throw TerminalNotFoundException::withId($command->terminalId);

        $terminal->deactivate();

        $this->terminalRepository->save($terminal);

        $this->eventPublisher->publish(new TerminalDeactivated(
            terminalId: $command->terminalId,
            clientId:   (string) $terminal->clientId(),
        ));
    }
}
