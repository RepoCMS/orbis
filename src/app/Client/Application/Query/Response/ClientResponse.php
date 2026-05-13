<?php

declare(strict_types=1);

namespace App\Client\Application\Query\Response;

use App\Client\Domain\Entities\Client;

final readonly class ClientResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public string $ruc,
        public ?string $address,
        public string $status,
    ) {
    }

    public static function fromEntity(Client $client): self
    {
        return new self(
            id:      (string) $client->id(),
            name:    (string) $client->name(),
            ruc:     (string) $client->ruc(),
            address: $client->address(),
            status:  $client->status()->value,
        );
    }
}
