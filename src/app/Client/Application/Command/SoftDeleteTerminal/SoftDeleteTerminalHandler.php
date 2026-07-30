<?php

declare(strict_types=1);

namespace App\Client\Application\Command\SoftDeleteTerminal;

use App\Client\Application\Exception\TerminalNotFoundException;
use App\Client\Domain\Repositories\TerminalRepositoryInterface;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;

final readonly class SoftDeleteTerminalHandler
{
    public function __construct(
        private TerminalRepositoryInterface $terminalRepository,
    ) {
    }

    public function __invoke(SoftDeleteTerminalCommand $command): void
    {
        $terminalId = new TerminalIdValueObject($command->terminalId);
        $terminal = $this->terminalRepository->findById($terminalId)
            ?? throw TerminalNotFoundException::withId($command->terminalId);

        $terminal->softDelete();

        $this->terminalRepository->save($terminal);
    }
}
