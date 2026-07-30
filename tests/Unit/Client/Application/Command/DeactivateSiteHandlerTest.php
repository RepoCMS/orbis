<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\DeactivateSite\DeactivateSiteCommand;
use App\Client\Application\Command\DeactivateSite\DeactivateSiteHandler;
use App\Client\Application\Exception\SiteNotFoundException;
use App\Client\Domain\Enums\SiteStatus;
use App\Client\Domain\Entities\Site;
use App\Client\Domain\Repositories\SiteRepositoryInterface;
use App\Client\Domain\ValueObjects\SiteIdValueObject;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use PHPUnit\Framework\TestCase;

final class DeactivateSiteHandlerTest extends TestCase
{
    private const SITE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testDeactivatesSiteSuccessfully(): void
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
        $repo->expects($this->once())->method('save');

        $handler = new DeactivateSiteHandler($repo);
        ($handler)(new DeactivateSiteCommand(siteId: self::SITE_ID));

        self::assertSame(SiteStatus::INACTIVE, $site->status());
    }

    public function testThrowsWhenSiteNotFound(): void
    {
        $repo = $this->createMock(SiteRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new DeactivateSiteHandler($repo);

        $this->expectException(SiteNotFoundException::class);
        ($handler)(new DeactivateSiteCommand(siteId: self::SITE_ID));
    }
}
