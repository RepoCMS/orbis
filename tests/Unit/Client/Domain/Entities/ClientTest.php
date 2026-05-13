<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Domain\Entities;

use App\Client\Domain\Entities\Client;
use App\Client\Domain\Enums\ClientStatus;
use App\Client\Domain\Exceptions\ClientStatusException;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ClientNameValueObject;
use App\Client\Domain\ValueObjects\ClientRucValueObject;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    private function createClient(?string $address = null): Client
    {
        return Client::register(
            new ClientIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new ClientNameValueObject('ACMS Solutions'),
            new ClientRucValueObject('1712345675001'),
            $address,
        );
    }

    // --- Registro ---

    public function testRegisterCreatesActiveClient(): void
    {
        $client = $this->createClient();

        self::assertSame(ClientStatus::ACTIVE, $client->status());
        self::assertTrue($client->isActive());
    }

    public function testRegisterStoresAllFields(): void
    {
        $client = $this->createClient('Av. Principal 123');

        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $client->id());
        self::assertSame('ACMS Solutions', (string) $client->name());
        self::assertSame('1712345675001', (string) $client->ruc());
        self::assertSame('Av. Principal 123', $client->address());
    }

    public function testRegisterWithNullAddress(): void
    {
        $client = $this->createClient();

        self::assertNull($client->address());
    }

    public function testRegisterTrimsAddress(): void
    {
        $client = $this->createClient('  Av. Principal 123  ');

        self::assertSame('Av. Principal 123', $client->address());
    }

    // --- Rename ---

    public function testRenameChangesName(): void
    {
        $client = $this->createClient();

        $client->changeName(new ClientNameValueObject('Nuevo Nombre SA'));

        self::assertSame('Nuevo Nombre SA', (string) $client->name());
    }

    // --- Change Address ---

    public function testChangeAddressUpdatesValue(): void
    {
        $client = $this->createClient();

        $client->changeAddress('Nueva dirección 456');

        self::assertSame('Nueva dirección 456', $client->address());
    }

    public function testChangeAddressToNull(): void
    {
        $client = $this->createClient('Dirección original');

        $client->changeAddress(null);

        self::assertNull($client->address());
    }

    public function testChangeAddressTrimsWhitespace(): void
    {
        $client = $this->createClient();

        $client->changeAddress('  con espacios  ');

        self::assertSame('con espacios', $client->address());
    }

    // --- Activate / Deactivate ---

    public function testDeactivateTransitionsToInactive(): void
    {
        $client = $this->createClient();
        $client->deactivate();

        self::assertSame(ClientStatus::INACTIVE, $client->status());
        self::assertFalse($client->isActive());
    }

    public function testDeactivateThrowsWhenAlreadyInactive(): void
    {
        $client = $this->createClient();
        $client->deactivate();

        $this->expectException(ClientStatusException::class);
        $client->deactivate();
    }

    public function testActivateTransitionsToActive(): void
    {
        $client = $this->createClient();
        $client->deactivate();
        $client->activate();

        self::assertSame(ClientStatus::ACTIVE, $client->status());
        self::assertTrue($client->isActive());
    }

    public function testActivateThrowsWhenAlreadyActive(): void
    {
        $client = $this->createClient();

        $this->expectException(ClientStatusException::class);
        $client->activate();
    }
}
