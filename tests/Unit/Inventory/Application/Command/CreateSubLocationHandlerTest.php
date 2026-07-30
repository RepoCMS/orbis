<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\CreateSubLocation\CreateSubLocationCommand;
use App\Inventory\Application\Command\CreateSubLocation\CreateSubLocationHandler;
use App\Inventory\Domain\Repositories\SubLocationRepositoryInterface;
use App\Inventory\Domain\ValueObjects\SubLocationIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class CreateSubLocationHandlerTest extends TestCase
{
    private const NEW_ID      = '01HZ0000000000000000000001';
    private const LOCATION_ID = '01HZ0000000000000000000002';

    public function testCreatesSubLocation(): void
    {
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::NEW_ID);

        $repo = $this->createMock(SubLocationRepositoryInterface::class);
        $repo->expects(self::once())->method('save');

        $handler = new CreateSubLocationHandler($repo, $idGenerator);
        $result = ($handler)(new CreateSubLocationCommand(
            locationId: self::LOCATION_ID,
            type: 'rack',
            name: 'Nueva sububicación',
            code: 'S-001',
        ));

        self::assertSame(self::NEW_ID, $result);
    }
}
