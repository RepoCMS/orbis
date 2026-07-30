<?php

declare(strict_types=1);

namespace App\Client\Application\Command\UpdateTerminal;

use App\Client\Application\Exception\SerialForTerminalAlreadyExistsException;
use App\Client\Application\Exception\TerminalNotFoundException;
use App\Client\Domain\Enums\TerminalFunctionality;
use App\Client\Domain\Enums\TerminalType;
use App\Client\Domain\Enums\TerminalVendor;
use App\Client\Domain\Events\TerminalUpdated;
use App\Client\Domain\Repositories\TerminalRepositoryInterface;
use App\Client\Domain\ValueObjects\AtmModelCodeValueObject;
use App\Client\Domain\ValueObjects\SerialNumberValueObject;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

final readonly class UpdateTerminalHandler
{
    public function __construct(
        private TerminalRepositoryInterface $terminalRepository,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(UpdateTerminalCommand $command): void
    {
        $terminalId = new TerminalIdValueObject($command->terminalId);
        $terminal   = $this->terminalRepository->findById($terminalId)
            ?? throw TerminalNotFoundException::withId($command->terminalId);

        $serial = new SerialNumberValueObject($command->serial);

        // Validar serial único (excluyendo el propio terminal)
        $existing = $this->terminalRepository->findBySerial($serial);
        if ($existing !== null && (string) $existing->id() !== $command->terminalId) {
            throw SerialForTerminalAlreadyExistsException::withSerial($command->serial);
        }

        $terminal->changeSerial($serial);
        $terminal->changeVendor(TerminalVendor::from($command->vendor));
        $terminal->changeFuncionality(TerminalFunctionality::from($command->funcionality));
        $terminal->changeType(TerminalType::from($command->type));

        $atmModelCode = $command->atmModelCode !== null
            ? new AtmModelCodeValueObject($command->atmModelCode)
            : null;
        $terminal->changeAtmModel($atmModelCode);

        $this->terminalRepository->save($terminal);

        $this->eventPublisher->publish(new TerminalUpdated(
            terminalId:   $command->terminalId,
            serialNumber: $command->serial,
        ));
    }
}
