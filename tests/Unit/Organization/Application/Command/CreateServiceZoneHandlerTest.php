<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Organization\Application\Command\CreateServiceZone\CreateServiceZoneCommand;
use App\Organization\Application\Command\CreateServiceZone\CreateServiceZoneHandler;
use App\Organization\Domain\Entities\ServiceZone;
use App\Organization\Domain\Exceptions\ServiceZoneException;
use App\Organization\Domain\Repositories\ServiceZoneRepositoryInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class CreateServiceZoneHandlerTest extends TestCase
{
    private const GENERATED_ID = '01ARZ3NDEKTSV4RRFFQ69G5XXX';
    private const ZONE_NAME    = 'Zona Centro';

    public function testCreatesServiceZoneSuccessfully(): void
    {
        $repo = $this->createMock(ServiceZoneRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $repo->method('existsByName')->willReturn(false);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $savedZone = null;
        $repo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (ServiceZone $z) use (&$savedZone): void {
                $savedZone = $z;
            });

        $handler = new CreateServiceZoneHandler($repo, $idGenerator);
        $result = ($handler)(new CreateServiceZoneCommand(
            name: self::ZONE_NAME,
        ));

        self::assertSame(self::GENERATED_ID, $result);
        self::assertNotNull($savedZone);
        self::assertSame(self::ZONE_NAME, $savedZone->name());
        self::assertTrue($savedZone->isActive());
    }

    public function testThrowsWhenNameAlreadyExists(): void
    {
        $repo = $this->createMock(ServiceZoneRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $repo->method('existsByName')->willReturn(true);

        $handler = new CreateServiceZoneHandler($repo, $idGenerator);

        $this->expectException(ServiceZoneException::class);
        $this->expectExceptionMessage('Ya existe una zona de servicio con ese nombre.');

        ($handler)(new CreateServiceZoneCommand(name: self::ZONE_NAME));
    }

    public function testThrowsWhenNameIsTooShort(): void
    {
        $repo = $this->createMock(ServiceZoneRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $repo->method('existsByName')->willReturn(false);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $handler = new CreateServiceZoneHandler($repo, $idGenerator);

        $this->expectException(ServiceZoneException::class);
        $this->expectExceptionMessage('debe tener al menos 3 caracteres');

        ($handler)(new CreateServiceZoneCommand(name: 'AB'));
    }
}
