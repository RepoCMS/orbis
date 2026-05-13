<?php

declare(strict_types=1);

namespace App\Client\Application\Query\Response;

use App\Client\Domain\Entities\Terminal;

final readonly class TerminalResponse
{
    public function __construct(
        public string $id,
        public string $clientId,
        public string $serial,
        public string $vendor,
        public string $funcionality,
        public string $type,
        public string $status,
    ) {
    }

    public static function fromEntity(Terminal $terminal): self
    {
        return new self(
            id:          (string) $terminal->id(),
            clientId:    (string) $terminal->clientId(),
            serial:      (string) $terminal->serial(),
            vendor:      $terminal->vendor()->value,
            funcionality: $terminal->funcionality()->value,
            type:        $terminal->type()->value,
            status:      $terminal->status()->value,
        );
    }
}
