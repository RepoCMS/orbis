<?php

declare(strict_types=1);

namespace App\Client\Presentation\Http;

use App\Client\Application\Command\ActivateTerminalAssignment\ActivateTerminalAssignmentCommand;
use App\Client\Application\Command\ActivateTerminalAssignment\ActivateTerminalAssignmentHandler;
use App\Client\Application\Command\DeactivateTerminalAssignment\DeactivateTerminalAssignmentCommand;
use App\Client\Application\Command\DeactivateTerminalAssignment\DeactivateTerminalAssignmentHandler;
use App\Client\Application\Command\RegisterTerminalAssignment\RegisterTerminalAssignmentCommand;
use App\Client\Application\Command\RegisterTerminalAssignment\RegisterTerminalAssignmentHandler;
use App\Client\Application\Command\UpdateTerminalAssignment\UpdateTerminalAssignmentCommand;
use App\Client\Application\Command\UpdateTerminalAssignment\UpdateTerminalAssignmentHandler;
use App\Client\Application\Exception\TerminalAssignmentNotFoundException;
use App\Client\Application\Exception\TerminalNotFoundException;
use App\Client\Application\Query\FindTerminalAssignmentById\FindTerminalAssignmentByIdHandler;
use App\Client\Application\Query\FindTerminalAssignmentById\FindTerminalAssignmentByIdQuery;
use App\Client\Application\Query\ListTerminalAssignments\ListTerminalAssignmentsHandler;
use App\Client\Application\Query\ListTerminalAssignments\ListTerminalAssignmentsQuery;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/client/terminal-assignments')]
final class ApiClientTerminalAssignmentController extends AbstractController
{
    #[IsGranted('client.view')]
    #[Route('', name: 'api_client_terminal_assignments_list', methods: ['GET'])]
    public function list(Request $request, ListTerminalAssignmentsHandler $handler): JsonResponse
    {
        $query = new ListTerminalAssignmentsQuery(
            page: (int) $request->query->get('page', 1),
            limit: (int) $request->query->get('limit', 20),
        );

        return $this->json(['data' => $handler($query)]);
    }

    #[IsGranted('client.update')]
    #[Route('', name: 'api_client_terminal_assignments_create', methods: ['POST'])]
    public function create(Request $request, RegisterTerminalAssignmentHandler $handler): JsonResponse
    {
        $payload = $request->toArray();

        try {
            $id = ($handler)(new RegisterTerminalAssignmentCommand(
                terminalId:         trim((string) ($payload['terminalId'] ?? '')),
                locationId:         trim((string) ($payload['locationId'] ?? '')),
                name:               trim((string) ($payload['name'] ?? '')),
                idAssignedByClient: trim((string) ($payload['idAssignedByClient'] ?? '')),
            ));

            return $this->json(['data' => ['id' => $id]], Response::HTTP_CREATED);
        } catch (TerminalNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('client.view')]
    #[Route('/{id}', name: 'api_client_terminal_assignments_show', methods: ['GET'])]
    public function show(string $id, FindTerminalAssignmentByIdHandler $handler): JsonResponse
    {
        try {
            $assignment = ($handler)(new FindTerminalAssignmentByIdQuery($id));

            return $this->json(['data' => $assignment]);
        } catch (TerminalAssignmentNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[IsGranted('client.update')]
    #[Route('/{id}', name: 'api_client_terminal_assignments_update', methods: ['PATCH'])]
    public function update(string $id, Request $request, UpdateTerminalAssignmentHandler $handler): JsonResponse
    {
        $payload = $request->toArray();

        try {
            ($handler)(new UpdateTerminalAssignmentCommand(
                id:                  $id,
                name:                trim((string) ($payload['name'] ?? '')),
                idAssignedByClient:  trim((string) ($payload['idAssignedByClient'] ?? '')),
                locationId:          trim((string) ($payload['locationId'] ?? '')),
            ));

            return $this->json(['data' => ['id' => $id]]);
        } catch (TerminalAssignmentNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('client.update')]
    #[Route('/{id}/deactivate', name: 'api_client_terminal_assignments_deactivate', methods: ['POST'])]
    public function deactivate(string $id, DeactivateTerminalAssignmentHandler $handler): JsonResponse
    {
        try {
            ($handler)(new DeactivateTerminalAssignmentCommand($id));

            return $this->json(['data' => ['id' => $id]]);
        } catch (TerminalAssignmentNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('client.update')]
    #[Route('/{id}/reactivate', name: 'api_client_terminal_assignments_reactivate', methods: ['POST'])]
    public function reactivate(string $id, ActivateTerminalAssignmentHandler $handler): JsonResponse
    {
        try {
            ($handler)(new ActivateTerminalAssignmentCommand($id));

            return $this->json(['data' => ['id' => $id]]);
        } catch (TerminalAssignmentNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
