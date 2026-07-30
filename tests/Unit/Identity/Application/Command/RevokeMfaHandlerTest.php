<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Command;

use App\Identity\Application\Command\RevokeMfa\RevokeMfaCommand;
use App\Identity\Application\Command\RevokeMfa\RevokeMfaHandler;
use App\Identity\Domain\Entities\UserMfaConfig;
use App\Identity\Domain\Repositories\AuthLogRepositoryInterface;
use App\Identity\Domain\Repositories\UserMfaConfigRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserMfaConfigIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class RevokeMfaHandlerTest extends TestCase
{
    private const USER_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testRevokesMfaSuccessfully(): void
    {
        $config = UserMfaConfig::setup(
            new UserMfaConfigIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FA1'),
            new UserIdValueObject(self::USER_ID),
            'JBSWY3DPEHPK3PXP',
        );
        $config->confirm();

        $mfaRepo = $this->createMock(UserMfaConfigRepositoryInterface::class);
        $mfaRepo->method('findByUserId')->willReturn($config);
        $mfaRepo->expects($this->once())->method('deleteByUserId');

        $authLogRepo = $this->createMock(AuthLogRepositoryInterface::class);
        $authLogRepo->expects($this->once())->method('append');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn('01ARZ3NDEKTSV4RRFFQ69G5FA2');

        $handler = new RevokeMfaHandler($mfaRepo, $authLogRepo, $idGenerator);
        ($handler)(new RevokeMfaCommand(self::USER_ID));

        self::assertTrue(true);
    }

    public function testIsIdempotentWhenNoConfig(): void
    {
        $mfaRepo = $this->createMock(UserMfaConfigRepositoryInterface::class);
        $mfaRepo->method('findByUserId')->willReturn(null);
        $mfaRepo->expects($this->never())->method('deleteByUserId');

        $handler = new RevokeMfaHandler(
            $mfaRepo,
            $this->createMock(AuthLogRepositoryInterface::class),
            $this->createMock(IdGeneratorInterface::class),
        );
        ($handler)(new RevokeMfaCommand(self::USER_ID));

        self::assertTrue(true);
    }
}
