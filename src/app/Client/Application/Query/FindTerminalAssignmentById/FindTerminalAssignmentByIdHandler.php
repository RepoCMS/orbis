<?php

declare(strict_types=1);

namespace App\Client\Application\Query\FindTerminalAssignmentById;

use App\Client\Application\Exception\TerminalAssignmentNotFoundException;
use App\Client\Application\Query\Response\TerminalAssignmentResponse;
use App\Client\Domain\Repositories\TerminalAssignmentRepositoryInterface;
use App\Client\Domain\ValueObjects\TerminalAssignmentIdValueObject;

final readonly class FindTerminalAssignmentByIdHandler
{
    public function __construct(
        private TerminalAssignmentRepositoryInterface $assignmentRepository,
    ) {
    }

    public function __invoke(FindTerminalAssignmentByIdQuery $query): TerminalAssignmentResponse
    {
        $assignmentId = new TerminalAssignmentIdValueObject($query->assignmentId);
        $assignment = $this->assignmentRepository->findById($assignmentId)
            ?? throw TerminalAssignmentNotFoundException::withId($query->assignmentId);

        return TerminalAssignmentResponse::fromEntity($assignment);
    }
}
