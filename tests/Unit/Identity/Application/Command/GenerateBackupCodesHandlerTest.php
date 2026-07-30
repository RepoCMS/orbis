<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Command;

use App\Identity\Application\Command\GenerateBackupCodes\GenerateBackupCodesCommand;
use App\Identity\Application\Command\GenerateBackupCodes\GenerateBackupCodesHandler;
use App\Identity\Domain\Repositories\UserMfaBackupCodeRepositoryInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class GenerateBackupCodesHandlerTest extends TestCase
{
    private const USER_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testGeneratesTenBackupCodes(): void
    {
        $repo = $this->createMock(UserMfaBackupCodeRepositoryInterface::class);
        $repo->expects($this->once())->method('deleteAllByUser');
        $repo->expects($this->exactly(10))->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(
            '01HZ0000000000000000000001',
            '01HZ0000000000000000000002',
            '01HZ0000000000000000000003',
            '01HZ0000000000000000000004',
            '01HZ0000000000000000000005',
            '01HZ0000000000000000000006',
            '01HZ0000000000000000000007',
            '01HZ0000000000000000000008',
            '01HZ0000000000000000000009',
            '01HZ0000000000000000000010',
        );

        $handler = new GenerateBackupCodesHandler($repo, $idGenerator);
        $codes = ($handler)(new GenerateBackupCodesCommand(self::USER_ID));

        self::assertCount(10, $codes);
        foreach ($codes as $code) {
            self::assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $code);
        }
    }
}
