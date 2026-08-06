<?php

declare(strict_types=1);

namespace App\Support\Application\Command\UploadFindingEvidence;

use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Exceptions\ApplicationException;
use App\Support\Domain\AtmModel\EvidenceMoment;
use App\Support\Domain\Entities\FindingEvidence;
use App\Support\Domain\Repositories\TicketModuleFindingRepositoryInterface;
use App\Support\Domain\ValueObjects\FindingEvidenceIdValueObject;
use App\Support\Domain\ValueObjects\TicketModuleFindingIdValueObject;

final readonly class UploadFindingEvidenceHandler
{
    public function __construct(
        private TicketModuleFindingRepositoryInterface $findingRepo,
        private IdGeneratorInterface                   $idGenerator,
    ) {
    }

    public function __invoke(UploadFindingEvidenceCommand $command): string
    {
        $findingId = TicketModuleFindingIdValueObject::fromString($command->findingId);

        $finding = $this->findingRepo->findById($findingId);
        if ($finding === null) {
            throw new ApplicationException(
                sprintf('Module finding con ID "%s" no encontrado.', $command->findingId)
            );
        }

        $evidence = FindingEvidence::upload(
            id: FindingEvidenceIdValueObject::fromString($this->idGenerator->generate()),
            findingId: $findingId,
            filePath: $command->filePath,
            originalName: $command->originalName,
            mimeType: $command->mimeType,
            moment: EvidenceMoment::from($command->moment),
            uploadedBy: $command->uploadedBy,
        );

        $finding->addEvidence($evidence);

        $this->findingRepo->save($finding);

        return (string) $evidence->id();
    }
}
