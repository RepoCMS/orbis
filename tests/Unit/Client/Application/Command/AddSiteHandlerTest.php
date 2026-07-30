<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\AddSite\AddSiteCommand;
use App\Client\Application\Command\AddSite\AddSiteHandler;
use App\Client\Domain\Repositories\SiteRepositoryInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class AddSiteHandlerTest extends TestCase
{
    private const GENERATED_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAW';
    private const TERMINAL_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAX';
    private const CITY_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAY';

    public function testAddSiteSuccessfully(): void
    {
        $repo = $this->createMock(SiteRepositoryInterface::class);
        $repo->expects($this->once())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $handler = new AddSiteHandler($repo, $idGenerator);
        $result = ($handler)(new AddSiteCommand(
            clientId: self::CLIENT_ID,
            cityId: self::CITY_ID,
            terminalId: self::TERMINAL_ID,
            name: 'Main Site',
        ));

        self::assertSame(self::GENERATED_ID, $result);
    }

    public function testAddSiteWithServiceZone(): void
    {
        $repo = $this->createMock(SiteRepositoryInterface::class);
        $repo->expects($this->once())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $handler = new AddSiteHandler($repo, $idGenerator);
        $result = ($handler)(new AddSiteCommand(
            clientId: self::CLIENT_ID,
            cityId: self::CITY_ID,
            terminalId: self::TERMINAL_ID,
            name: 'Main Site',
            serviceZoneId: '01ARZ3NDEKTSV4RRFFQ69G5FAB',
        ));

        self::assertSame(self::GENERATED_ID, $result);
    }
}
