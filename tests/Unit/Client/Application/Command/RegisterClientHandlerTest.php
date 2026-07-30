<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\RegisterClient\RegisterClientCommand;
use App\Client\Application\Command\RegisterClient\RegisterClientHandler;
use App\Client\Application\Exception\ClientRucAlreadyExistsException;
use App\Client\Domain\Entities\Client;
use App\Client\Domain\Enums\ClientStatus;
use App\Client\Domain\Repositories\ClientRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientRucValueObject;
use App\Shared\Application\Service\TransactionManagerInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class RegisterClientHandlerTest extends TestCase
{
    private const GENERATED_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testRegistersClientSuccessfully(): void
    {
        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findByRuc')->willReturn(null);

        $savedClient = null;
        $clientRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Client $c) use (&$savedClient): void {
                $savedClient = $c;
            });

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $transactionManager = $this->createStub(TransactionManagerInterface::class);
        $handler = new RegisterClientHandler($clientRepo, $transactionManager, $idGenerator);
        ($handler)(new RegisterClientCommand(
            name: 'Test Client',
            ruc: '1712345675001',
            address: 'Dir Principal',
        ));

        self::assertNotNull($savedClient);
        self::assertSame(self::GENERATED_ID, (string) $savedClient->id());
        self::assertTrue($savedClient->isActive());
    }

    public function testRegistersClientWithoutAddress(): void
    {
        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findByRuc')->willReturn(null);
        $clientRepo->expects($this->once())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $transactionManager = $this->createStub(TransactionManagerInterface::class);
        $handler = new RegisterClientHandler($clientRepo, $transactionManager, $idGenerator);
        ($handler)(new RegisterClientCommand(
            name: 'Test Client',
            ruc: '1712345675001',
        ));

        self::assertTrue(true);
    }

    public function testThrowsWhenRucAlreadyExists(): void
    {
        $existingClient = Client::createForTest(
            new \App\Client\Domain\ValueObjects\ClientIdValueObject(self::GENERATED_ID),
            new \App\Client\Domain\ValueObjects\ClientNameValueObject('Existing'),
            new ClientRucValueObject('1712345675001'),
            status: ClientStatus::ACTIVE,
        );

        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findByRuc')->willReturn($existingClient);
        $clientRepo->expects($this->never())->method('save');

        $transactionManager = $this->createStub(TransactionManagerInterface::class);
        $handler = new RegisterClientHandler($clientRepo, $transactionManager, $this->createMock(IdGeneratorInterface::class));

        $this->expectException(ClientRucAlreadyExistsException::class);
        $this->expectExceptionMessage('El RUC "1712345675001" ya está registrado.');

        ($handler)(new RegisterClientCommand(
            name: 'Test Client',
            ruc: '1712345675001',
        ));
    }

    public function testThrowsOnInvalidRuc(): void
    {
        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->expects($this->never())->method('save');

        $transactionManager = $this->createStub(TransactionManagerInterface::class);
        $handler = new RegisterClientHandler($clientRepo, $transactionManager, $this->createMock(IdGeneratorInterface::class));

        $this->expectException(\App\Client\Domain\Exceptions\ClientRucException::class);

        ($handler)(new RegisterClientCommand(
            name: 'Test Client',
            ruc: 'invalid-ruc',
        ));
    }
}
