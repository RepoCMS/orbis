<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\GenerateBackupCodes;

use App\Identity\Domain\Entities\UserMfaBackupCode;
use App\Identity\Domain\Repositories\UserMfaBackupCodeRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserMfaBackupCodeIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;
use Random\RandomException;

final readonly class GenerateBackupCodesHandler
{
    private const CODES_COUNT = 10;
    private const CODE_LENGTH = 12;

    public function __construct(
        private UserMfaBackupCodeRepositoryInterface $backupCodeRepo,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    /**
     * @return string[] Los códigos generados
     */
    public function __invoke(GenerateBackupCodesCommand $command): array
    {
        $userId = new UserIdValueObject($command->userId);

        $this->backupCodeRepo->deleteAllByUser($userId);

        $codes = [];
        for ($i = 0; $i < self::CODES_COUNT; $i++) {
            $code = $this->generateCode();
            $backupCode = UserMfaBackupCode::generate(
                new UserMfaBackupCodeIdValueObject($this->idGenerator->generate()),
                $userId,
                $code,
            );
            $this->backupCodeRepo->save($backupCode);
            $codes[] = $code;
        }

        return $codes;
    }

    private function generateCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return implode('-', str_split($code, 4));
    }
}
