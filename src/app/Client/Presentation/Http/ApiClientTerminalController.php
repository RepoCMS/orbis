<?php

declare(strict_types=1);

namespace App\Client\Presentation\Http;

use App\Client\Application\Command\ActivateTerminal\ActivateTerminalCommand;
use App\Client\Application\Command\ActivateTerminal\ActivateTerminalHandler;
use App\Client\Application\Command\DeactivateTerminal\DeactivateTerminalCommand;
use App\Client\Application\Command\DeactivateTerminal\DeactivateTerminalHandler;
use App\Client\Application\Command\RegisterTerminal\RegisterTerminalCommand;
use App\Client\Application\Command\RegisterTerminal\RegisterTerminalHandler;
use App\Client\Application\Command\UpdateTerminal\UpdateTerminalCommand;
use App\Client\Application\Command\UpdateTerminal\UpdateTerminalHandler;
use App\Client\Application\Exception\ClientNotFoundException;
use App\Client\Application\Exception\SerialForTerminalAlreadyExistsException;
use App\Client\Application\Exception\TerminalNotFoundException;
use App\Client\Application\Query\FindTerminalsByClient\FindTerminalByIdHandler;
use App\Client\Application\Query\FindTerminalsByClient\FindTerminalByIdQuery;
use App\Client\Application\Query\FindTerminalsByClient\FindTerminalsByClientHandler;
use App\Client\Application\Query\FindTerminalsByClient\FindTerminalsByClientQuery;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/client/clients/{clientId}/terminals')]
final class ApiClientTerminalController extends AbstractController
{
    #[IsGranted('client.view')]
    #[Route('', name: 'api_client_terminals_list', methods: ['GET'])]
    public function list(string $clientId, FindTerminalsByClientHandler $handler): JsonResponse
    {
        return $this->json(['data' => $handler(new FindTerminalsByClientQuery($clientId))]);
    }

    #[IsGranted('client.update')]
    #[Route('', name: 'api_client_terminals_create', methods: ['POST'])]
    public function create(string $clientId, Request $request, RegisterTerminalHandler $handler): JsonResponse
    {
        $payload = $request->toArray();

        try {
            $atmModelCode = isset($payload['atmModelCode'])
                ? trim((string) $payload['atmModelCode'])
                : null;

            ($handler)(new RegisterTerminalCommand(
                clientId:      $clientId,
                serial:        trim((string) ($payload['serial'] ?? '')),
                vendor:        trim((string) ($payload['vendor'] ?? '')),
                funcionality:  trim((string) ($payload['funcionality'] ?? '')),
                type:          trim((string) ($payload['type'] ?? '')),
                atmModelCode:  $atmModelCode !== '' ? $atmModelCode : null,
            ));

            return $this->json(['data' => ['clientId' => $clientId]], Response::HTTP_CREATED);
        } catch (ClientNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (SerialForTerminalAlreadyExistsException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('client.view')]
    #[Route('/{id}', name: 'api_client_terminals_show', methods: ['GET'])]
    public function show(string $clientId, string $id, FindTerminalByIdHandler $handler): JsonResponse
    {
        try {
            $terminal = ($handler)(new FindTerminalByIdQuery($id));
            return $this->json(['data' => $terminal]);
        } catch (TerminalNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[IsGranted('client.update')]
    #[Route('/{id}', name: 'api_client_terminals_update', methods: ['PATCH'])]
    public function update(string $clientId, string $id, Request $request, UpdateTerminalHandler $handler): JsonResponse
    {
        $payload = $request->toArray();

        try {
            $atmModelCode = isset($payload['atmModelCode'])
                ? trim((string) $payload['atmModelCode'])
                : null;

            ($handler)(new UpdateTerminalCommand(
                terminalId:    $id,
                serial:        trim((string) ($payload['serial'] ?? '')),
                vendor:        trim((string) ($payload['vendor'] ?? '')),
                funcionality:  trim((string) ($payload['funcionality'] ?? '')),
                type:          trim((string) ($payload['type'] ?? '')),
                atmModelCode:  $atmModelCode !== '' ? $atmModelCode : null,
            ));

            return $this->json(['data' => ['terminalId' => $id]]);
        } catch (TerminalNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (SerialForTerminalAlreadyExistsException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('client.update')]
    #[Route('/{id}/deactivate', name: 'api_client_terminals_deactivate', methods: ['POST'])]
    public function deactivate(string $clientId, string $id, DeactivateTerminalHandler $handler): JsonResponse
    {
        try {
            ($handler)(new DeactivateTerminalCommand($id));
            return $this->json(['data' => ['terminalId' => $id, 'status' => 'deactivated']]);
        } catch (TerminalNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[IsGranted('client.update')]
    #[Route('/{id}/reactivate', name: 'api_client_terminals_reactivate', methods: ['POST'])]
    public function reactivate(string $clientId, string $id, ActivateTerminalHandler $handler): JsonResponse
    {
        try {
            ($handler)(new ActivateTerminalCommand($id));
            return $this->json(['data' => ['terminalId' => $id, 'status' => 'reactivated']]);
        } catch (TerminalNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
