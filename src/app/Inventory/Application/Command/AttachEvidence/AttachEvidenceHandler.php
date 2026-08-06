<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\AttachEvidence;

use App\Inventory\Application\Exception\RepairNotFoundException;
use App\Inventory\Domain\Entities\RepairEvidence;
use App\Inventory\Domain\Enums\EvidenceType;
use App\Inventory\Domain\Repositories\RepairEvidenceRepositoryInterface;
use App\Inventory\Domain\Repositories\RepairRepositoryInterface;
use App\Inventory\Domain\ValueObjects\RepairEvidenceIdValueObject;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Infrastructure\DbExceptionHandlerTrait;

final readonly class AttachEvidenceHandler
{
    use DbExceptionHandlerTrait;

    public function __construct(
        private RepairRepositoryInterface $repairRepository,
        private RepairEvidenceRepositoryInterface $evidenceRepository,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(AttachEvidenceCommand $command): string
    {
        $repairId = new RepairIdValueObject($command->repairId);

        // Validar que la reparación existe
        $this->repairRepository->findById($repairId)
            ?? throw RepairNotFoundException::withId($command->repairId);

        $ulidRaw = $this->idGenerator->generate();

        $this->wrapDbExceptions(function () use ($command, $repairId, $ulidRaw): void {
            $evidence = RepairEvidence::upload(
                id:         new RepairEvidenceIdValueObject($ulidRaw),
                repairId:   $repairId,
                filePath:   $command->filePath,
                type:       EvidenceType::from($command->type),
                uploadedBy: $command->uploadedBy,
                notes:      $command->notes,
            );

            $this->evidenceRepository->save($evidence);
        });

        return $ulidRaw;
    }
}
