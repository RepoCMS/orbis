<?php

declare(strict_types=1);

namespace App\Client\Application\Command\ActivateTerminal;

use App\Client\Application\Exception\TerminalNotFoundException;
use App\Client\Domain\Events\TerminalReactivated;
use App\Client\Domain\Repositories\TerminalRepositoryInterface;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

final readonly class ActivateTerminalHandler
{
    public function __construct(
        private TerminalRepositoryInterface $terminalRepository,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(ActivateTerminalCommand $command): void
    {
        $terminalId = new TerminalIdValueObject($command->terminalId);
        $terminal   = $this->terminalRepository->findById($terminalId)
            ?? throw TerminalNotFoundException::withId($command->terminalId);

        $terminal->activate();

        $this->terminalRepository->save($terminal);

        $this->eventPublisher->publish(new TerminalReactivated(
            terminalId: $command->terminalId,
        ));
    }
}
