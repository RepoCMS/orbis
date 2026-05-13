<?php

declare(strict_types=1);

namespace App\Client\Application\Command\RegisterTerminal;

use App\Client\Application\Exception\ClientNotFoundException;
use App\Client\Domain\Entities\Terminal;
use App\Client\Domain\Enums\TerminalFunctionality;
use App\Client\Domain\Enums\TerminalType;
use App\Client\Domain\Enums\TerminalVendor;
use App\Client\Domain\Repositories\TerminalRepositoryInterface;
use App\Client\Domain\Repositories\ClientRepositoryInterface;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\SerialNumberValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class RegisterTerminalHandler
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
        private TerminalRepositoryInterface $terminalRepository,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(RegisterTerminalCommand $command): void
    {
        $clientId = new ClientIdValueObject($command->clientId);

        if ($this->clientRepository->findById($clientId) === null) {
            throw ClientNotFoundException::withId($command->clientId);
        }

        $terminal = Terminal::add(
            id:           new TerminalIdValueObject($this->idGenerator->generate()),
            clientId:     $clientId,
            serial:       new SerialNumberValueObject($command->serial),
            vendor:       TerminalVendor::from($command->vendor),
            funcionality: TerminalFunctionality::from($command->funcionality),
            type:         TerminalType::from($command->type),
        );

        $this->terminalRepository->save($terminal);
    }
}
