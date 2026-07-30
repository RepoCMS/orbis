<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\CreateLocation\CreateLocationCommand;
use App\Inventory\Application\Command\CreateLocation\CreateLocationHandler;
use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class CreateLocationHandlerTest extends TestCase
{
    public function testCreatesLocation(): void
    {
        $repo = $this->createMock(LocationRepositoryInterface::class);
        $repo->expects($this->once())->method('save')
            ->with($this->isInstanceOf(Location::class));

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn('01JARZ3NDEKTSV4RRFFQ69G5FV');

        $handler = new CreateLocationHandler($repo, $idGenerator);
        ($handler)(new CreateLocationCommand(
            name: 'Bodega Principal',
            city: 'Quito',
            address: 'Av. Amazonas 1234',
            type: 'warehouse',
        ));
    }
}
