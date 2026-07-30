<?php

declare(strict_types=1);

namespace App\Client\Presentation\Http;

use App\Client\Application\Command\RegisterWarranty\RegisterWarrantyCommand;
use App\Client\Application\Command\RegisterWarranty\RegisterWarrantyHandler;
use App\Client\Application\Command\UpdateWarranty\UpdateWarrantyCommand;
use App\Client\Application\Command\UpdateWarranty\UpdateWarrantyHandler;
use App\Client\Application\Exception\TerminalNotFoundException;
use App\Client\Application\Exception\TerminalWarrantyNotFoundException;
use App\Client\Application\Query\FindWarrantiesByTerminal\FindWarrantiesByTerminalHandler;
use App\Client\Application\Query\FindWarrantiesByTerminal\FindWarrantiesByTerminalQuery;
use App\Client\Application\Query\FindWarrantyById\FindWarrantyByIdHandler;
use App\Client\Application\Query\FindWarrantyById\FindWarrantyByIdQuery;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/client/clients/{clientId}/terminals/{terminalId}/warranties')]
final class ApiClientTerminalWarrantyController extends AbstractController
{
    #[IsGranted('client.view')]
    #[Route('', name: 'api_client_terminal_warranties_list', methods: ['GET'])]
    public function list(string $terminalId, FindWarrantiesByTerminalHandler $handler): JsonResponse
    {
        return $this->json(['data' => $handler(new FindWarrantiesByTerminalQuery($terminalId))]);
    }

    #[IsGranted('client.update')]
    #[Route('', name: 'api_client_terminal_warranties_create', methods: ['POST'])]
    public function create(string $terminalId, Request $request, RegisterWarrantyHandler $handler): JsonResponse
    {
        $payload = $request->toArray();

        try {
            ($handler)(new RegisterWarrantyCommand(
                terminalId: $terminalId,
                type:       trim((string) ($payload['type'] ?? '')),
                startDate:  trim((string) ($payload['startDate'] ?? '')),
                endDate:    trim((string) ($payload['endDate'] ?? '')),
                terms:      isset($payload['terms']) ? trim((string) $payload['terms']) : null,
            ));

            return $this->json(['data' => ['terminalId' => $terminalId]], Response::HTTP_CREATED);
        } catch (TerminalNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('client.view')]
    #[Route('/{id}', name: 'api_client_terminal_warranties_show', methods: ['GET'])]
    public function show(string $terminalId, string $id, FindWarrantyByIdHandler $handler): JsonResponse
    {
        try {
            return $this->json(['data' => $handler(new FindWarrantyByIdQuery($id))]);
        } catch (TerminalWarrantyNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[IsGranted('client.update')]
    #[Route('/{id}', name: 'api_client_terminal_warranties_update', methods: ['PATCH'])]
    public function update(string $terminalId, string $id, Request $request, UpdateWarrantyHandler $handler): JsonResponse
    {
        $payload = $request->toArray();

        try {
            ($handler)(new UpdateWarrantyCommand(
                warrantyId: $id,
                type:       isset($payload['type']) ? trim((string) $payload['type']) : null,
                endDate:    isset($payload['endDate']) ? trim((string) $payload['endDate']) : null,
                terms:      isset($payload['terms']) ? trim((string) $payload['terms']) : null,
            ));

            return $this->json(['data' => ['id' => $id]]);
        } catch (TerminalWarrantyNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
