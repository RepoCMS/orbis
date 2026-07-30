<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\DeleteClient\DeleteClientCommand;
use App\Client\Application\Command\DeleteClient\DeleteClientHandler;
use App\Client\Application\Exception\ClientNotFoundException;
use App\Client\Domain\Entities\Client;
use App\Client\Domain\Enums\ClientStatus;
use App\Client\Domain\Repositories\ClientRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ClientNameValueObject;
use App\Client\Domain\ValueObjects\ClientRucValueObject;
use PHPUnit\Framework\TestCase;

final class DeleteClientHandlerTest extends TestCase
{
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    private function makeClient(): Client
    {
        return Client::createForTest(
            new ClientIdValueObject(self::CLIENT_ID),
            new ClientNameValueObject('Test Client'),
            new ClientRucValueObject('1712345675001'),
            'Address',
            ClientStatus::ACTIVE,
        );
    }

    public function testSoftDeletesClientSuccessfully(): void
    {
        $client = $this->makeClient();

        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findById')->willReturn($client);

        $savedClient = null;
        $clientRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Client $c) use (&$savedClient): void {
                $savedClient = $c;
            });

        $handler = new DeleteClientHandler($clientRepo);
        ($handler)(new DeleteClientCommand(self::CLIENT_ID, 'Test reason'));

        self::assertNotNull($savedClient);
        self::assertTrue($savedClient->isDeleted());
        self::assertNotNull($savedClient->deletedAt());
    }

    public function testThrowsWhenClientNotFound(): void
    {
        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findById')->willReturn(null);
        $clientRepo->expects($this->never())->method('save');

        $handler = new DeleteClientHandler($clientRepo);

        $this->expectException(ClientNotFoundException::class);
        $this->expectExceptionMessage(self::CLIENT_ID);

        ($handler)(new DeleteClientCommand(self::CLIENT_ID));
    }
}
