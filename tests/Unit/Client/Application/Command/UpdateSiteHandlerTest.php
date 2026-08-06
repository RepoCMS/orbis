<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\UpdateSite\UpdateSiteCommand;
use App\Client\Application\Command\UpdateSite\UpdateSiteHandler;
use App\Client\Application\Exception\SiteNotFoundException;
use App\Client\Domain\Entities\Site;
use App\Client\Domain\Repositories\SiteRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\SiteIdValueObject;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use PHPUnit\Framework\TestCase;

final class UpdateSiteHandlerTest extends TestCase
{
    private const SITE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testUpdatesSiteSuccessfully(): void
    {
        $site = Site::add(
            id: new SiteIdValueObject(self::SITE_ID),
            clientId: new ClientIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAW'),
            cityId: '01ARZ3NDEKTSV4RRFFQ69G5FAX',
            terminalId: new TerminalIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAY'),
            name: 'Original Name',
        );

        $repo = $this->createMock(SiteRepositoryInterface::class);
        $repo->method('findById')->willReturn($site);
        $saved = null;
        $repo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function ($s) use (&$saved): void {
                $saved = $s;
            });

        $handler = new UpdateSiteHandler($repo);
        ($handler)(new UpdateSiteCommand(
            siteId: self::SITE_ID,
            name: 'Updated Name',
        ));

        self::assertSame('Updated Name', $saved->name());
    }

    public function testThrowsWhenSiteNotFound(): void
    {
        $repo = $this->createMock(SiteRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new UpdateSiteHandler($repo);

        $this->expectException(SiteNotFoundException::class);
        ($handler)(new UpdateSiteCommand(
            siteId: self::SITE_ID,
            name: 'Should Fail',
        ));
    }
}
