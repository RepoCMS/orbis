<?php

declare(strict_types=1);

namespace App\Client\Application\Query\ListTerminalAssignments;

use App\Client\Application\Query\Response\TerminalAssignmentResponse;
use App\Client\Domain\Repositories\TerminalAssignmentRepositoryInterface;

final readonly class ListTerminalAssignmentsHandler
{
    public function __construct(
        private TerminalAssignmentRepositoryInterface $assignmentRepository,
    ) {
    }

    /**
     * @return TerminalAssignmentResponse[]
     */
    public function __invoke(ListTerminalAssignmentsQuery $query): array
    {
        $assignments = $this->assignmentRepository->findAll();

        return array_map(
            static fn ($assignment) => TerminalAssignmentResponse::fromEntity($assignment),
            $assignments,
        );
    }
}
