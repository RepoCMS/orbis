<?php

declare(strict_types=1);

namespace App\Client\Application\Query\FindTerminalsByClient;

use App\Client\Application\Query\Response\TerminalResponse;
use App\Client\Domain\Repositories\TerminalRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;

final readonly class FindTerminalsByClientHandler
{
    public function __construct(
        private TerminalRepositoryInterface $terminalRepository,
    ) {
    }

    /**
     * @return TerminalResponse[]
     */
    public function __invoke(FindTerminalsByClientQuery $query): array
    {
        return array_map(
            static fn ($terminal) => TerminalResponse::fromEntity($terminal),
            $this->terminalRepository->findByClientId(new ClientIdValueObject($query->clientId)),
        );
    }
}
