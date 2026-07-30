<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\RegisterTerminal\RegisterTerminalCommand;
use App\Client\Application\Command\RegisterTerminal\RegisterTerminalHandler;
use App\Client\Application\Exception\ClientNotFoundException;
use App\Client\Domain\Entities\Client;
use App\Client\Domain\Enums\ClientStatus;
use App\Client\Domain\Repositories\ClientRepositoryInterface;
use App\Client\Domain\Repositories\TerminalRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ClientNameValueObject;
use App\Client\Domain\ValueObjects\ClientRucValueObject;
use App\Shared\Application\Service\TransactionManagerInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class RegisterTerminalHandlerTest extends TestCase
{
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const GENERATED_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAW';
    private const VALID_RUC = '1712345675001';

    private function makeClient(): Client
    {
        return Client::createForTest(
            new ClientIdValueObject(self::CLIENT_ID),
            new ClientNameValueObject('Test Client'),
            new ClientRucValueObject(self::VALID_RUC),
            'Address',
            ClientStatus::ACTIVE,
        );
    }

    public function testRegistersTerminalSuccessfully(): void
    {
        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findById')->willReturn($this->makeClient());

        $terminalRepo = $this->createMock(TerminalRepositoryInterface::class);
        $saved = null;
        $terminalRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function ($t) use (&$saved): void {
                $saved = $t;
            });

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $transactionManager = $this->createStub(TransactionManagerInterface::class);
        $handler = new RegisterTerminalHandler($clientRepo, $terminalRepo, $transactionManager, $idGenerator);
        ($handler)(new RegisterTerminalCommand(
            clientId: self::CLIENT_ID,
            serial: 'ATM0000001',
            vendor: 'hyosung',
            funcionality: 'dispenser',
            type: 'lobby',
        ));

        self::assertNotNull($saved);
        self::assertSame(self::GENERATED_ID, (string) $saved->id());
        self::assertSame(self::CLIENT_ID, (string) $saved->clientId());
    }

    public function testThrowsWhenClientNotFound(): void
    {
        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findById')->willReturn(null);

        $transactionManager = $this->createStub(TransactionManagerInterface::class);
        $handler = new RegisterTerminalHandler(
            $clientRepo,
            $this->createMock(TerminalRepositoryInterface::class),
            $transactionManager,
            $this->createMock(IdGeneratorInterface::class),
        );

        $this->expectException(ClientNotFoundException::class);
        $this->expectExceptionMessage('Cliente con ID "' . self::CLIENT_ID . '" no encontrado.');

        ($handler)(new RegisterTerminalCommand(
            clientId: self::CLIENT_ID,
            serial: 'ATM0000001',
            vendor: 'hyosung',
            funcionality: 'dispenser',
            type: 'lobby',
        ));
    }

    public function testThrowsOnInvalidVendor(): void
    {
        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findById')->willReturn($this->makeClient());

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $transactionManager = $this->createStub(TransactionManagerInterface::class);
        $handler = new RegisterTerminalHandler(
            $clientRepo,
            $this->createMock(TerminalRepositoryInterface::class),
            $transactionManager,
            $idGenerator,
        );

        $this->expectException(\ValueError::class);

        ($handler)(new RegisterTerminalCommand(
            clientId: self::CLIENT_ID,
            serial: 'ATM0000001',
            vendor: 'invalid_vendor',
            funcionality: 'dispenser',
            type: 'lobby',
        ));
    }

    public function testThrowsOnInvalidSerial(): void
    {
        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findById')->willReturn($this->makeClient());

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $transactionManager = $this->createStub(TransactionManagerInterface::class);
        $handler = new RegisterTerminalHandler(
            $clientRepo,
            $this->createMock(TerminalRepositoryInterface::class),
            $transactionManager,
            $idGenerator,
        );

        $this->expectException(\App\Client\Domain\Exceptions\SerialNumberException::class);

        ($handler)(new RegisterTerminalCommand(
            clientId: self::CLIENT_ID,
            serial: 'SHORT', // < 10 chars
            vendor: 'hyosung',
            funcionality: 'dispenser',
            type: 'lobby',
        ));
    }
}
