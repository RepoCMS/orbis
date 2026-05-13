<?php

declare(strict_types=1);

namespace App\Client\Application\Query\FindTerminalsByClient;

use App\Client\Application\Exception\TerminalNotFoundException;
use App\Client\Application\Query\Response\TerminalResponse;
use App\Client\Domain\Repositories\TerminalRepositoryInterface;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;

final readonly class FindTerminalByIdHandler
{
    public function __construct(
        private TerminalRepositoryInterface $terminalRepository,
    ) {
    }

    public function __invoke(FindTerminalByIdQuery $query): TerminalResponse
    {
        $terminal = $this->terminalRepository->findById(new TerminalIdValueObject($query->terminalId))
            ?? throw TerminalNotFoundException::withId($query->terminalId);

        return TerminalResponse::fromEntity($terminal);
    }
}
