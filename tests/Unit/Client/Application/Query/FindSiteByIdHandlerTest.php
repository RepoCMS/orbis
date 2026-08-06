<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Query;

use App\Client\Application\Exception\SiteNotFoundException;
use App\Client\Application\Query\FindSiteById\FindSiteByIdHandler;
use App\Client\Application\Query\FindSiteById\FindSiteByIdQuery;
use App\Client\Application\Query\Response\SiteResponse;
use App\Client\Domain\Entities\Site;
use App\Client\Domain\Repositories\SiteRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\SiteIdValueObject;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use PHPUnit\Framework\TestCase;

final class FindSiteByIdHandlerTest extends TestCase
{
    private const SITE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testReturnsSiteResponse(): void
    {
        $site = Site::add(
            id: new SiteIdValueObject(self::SITE_ID),
            clientId: new ClientIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAW'),
            cityId: '01ARZ3NDEKTSV4RRFFQ69G5FAX',
            terminalId: new TerminalIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAY'),
            name: 'Main Site',
        );

        $repo = $this->createMock(SiteRepositoryInterface::class);
        $repo->method('findById')->willReturn($site);

        $handler = new FindSiteByIdHandler($repo);
        $response = ($handler)(new FindSiteByIdQuery(siteId: self::SITE_ID));

        self::assertInstanceOf(SiteResponse::class, $response);
        self::assertSame('Main Site', $response->name);
    }

    public function testThrowsWhenSiteNotFound(): void
    {
        $repo = $this->createMock(SiteRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new FindSiteByIdHandler($repo);

        $this->expectException(SiteNotFoundException::class);
        ($handler)(new FindSiteByIdQuery(siteId: self::SITE_ID));
    }
}
