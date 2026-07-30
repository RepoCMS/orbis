<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\SubmitRequest;

use App\Inventory\Application\ValueObjects\RequestItemCommandInput;

final readonly class SubmitRequestCommand
{
    public function __construct(
        /** @var RequestItemCommandInput[] */
        public array $items,
        public string $sourceLocationId,
        public string $destinationLocationId,
        public string $requestedBy,
        public ?string $notes,
    ) {
    }
}
