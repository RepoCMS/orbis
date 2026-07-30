<?php

declare(strict_types=1);

namespace App\Client\Domain\Repositories;

use App\Client\Domain\Entities\Terminal;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\SerialNumberValueObject;

interface TerminalRepositoryInterface
{
    public function findById(TerminalIdValueObject $id): ?Terminal;

    public function findBySerial(SerialNumberValueObject $serial): ?Terminal;

    /**
     * @return Terminal[]
     */
    public function findByClientId(ClientIdValueObject $clientId): array;

    public function save(Terminal $terminal): void;

    public function delete(TerminalIdValueObject $id): void;
}
