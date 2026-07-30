<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CreateTransfer;

final readonly class CreateTransferCommand
{
    /**
     * @param string $destinationLocationId Ubicación destino
     * @param array<array{stockId: string, type: string, productId: string, quantity?: int}> $items Items a transferir
     * @param string|null $notes            Nota opcional
     * @param string $createdBy             ULID del usuario que realiza la transferencia
     */
    public function __construct(
        public string  $destinationLocationId,
        public array   $items,
        public ?string $notes = null,
        public string  $createdBy = '',
    ) {
    }
}
