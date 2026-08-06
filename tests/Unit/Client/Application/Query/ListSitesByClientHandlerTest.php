<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Query;

use App\Client\Application\Query\ListSitesByClient\ListSitesByClientHandler;
use App\Client\Application\Query\ListSitesByClient\ListSitesByClientQuery;
use App\Client\Application\Query\Response\SiteResponse;
use App\Client\Domain\Entities\Site;
use App\Client\Domain\Repositories\SiteRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\SiteIdValueObject;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use PHPUnit\Framework\TestCase;

final class ListSitesByClientHandlerTest extends TestCase
{
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const TERMINAL_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAW';

    public function testReturnsSiteResponses(): void
    {
        $site = Site::add(
            id: new SiteIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAX'),
            clientId: new ClientIdValueObject(self::CLIENT_ID),
            cityId: '01ARZ3NDEKTSV4RRFFQ69G5FAY',
            terminalId: new TerminalIdValueObject(self::TERMINAL_ID),
            name: 'Main Site',
        );

        $repo = $this->createMock(SiteRepositoryInterface::class);
        $repo->method('findByClientId')->willReturn([$site]);

        $handler = new ListSitesByClientHandler($repo);
        $result = ($handler)(new ListSitesByClientQuery(clientId: self::CLIENT_ID));

        self::assertCount(1, $result);
        self::assertInstanceOf(SiteResponse::class, $result[0]);
        self::assertSame('Main Site', $result[0]->name);
    }

    public function testReturnsEmptyArrayWhenNoSites(): void
    {
        $repo = $this->createMock(SiteRepositoryInterface::class);
        $repo->method('findByClientId')->willReturn([]);

        $handler = new ListSitesByClientHandler($repo);
        $result = ($handler)(new ListSitesByClientQuery(clientId: self::CLIENT_ID));

        self::assertCount(0, $result);
    }
}
