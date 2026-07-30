<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Command;

use App\Identity\Application\Command\StoreRefreshToken\StoreRefreshTokenCommand;
use App\Identity\Application\Command\StoreRefreshToken\StoreRefreshTokenHandler;
use App\Identity\Domain\Repositories\RefreshTokenRepositoryInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class StoreRefreshTokenHandlerTest extends TestCase
{
    private const USER_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testStoresRefreshTokenSuccessfully(): void
    {
        $repo = $this->createMock(RefreshTokenRepositoryInterface::class);
        $repo->expects($this->once())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn('01ARZ3NDEKTSV4RRFFQ69G5FA2');

        $handler = new StoreRefreshTokenHandler($repo, $idGenerator);
        ($handler)(new StoreRefreshTokenCommand(self::USER_ID, 'plain_token_value_here'));

        self::assertTrue(true); // no exception means success
    }
}
