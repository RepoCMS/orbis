<?php

declare(strict_types=1);

namespace App\Client\Presentation\Http;

use App\Client\Application\Command\ImportClients\ImportClientsCommand;
use App\Client\Application\Command\ImportClients\ImportClientsHandler;
use App\Client\Application\Command\ImportTerminals\ImportTerminalsCommand;
use App\Client\Application\Command\ImportTerminals\ImportTerminalsHandler;
use App\Shared\Application\Import\ImportResult;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/client/import')]
final class ApiClientImportController extends AbstractController
{
    public function __construct(
        private readonly ImportClientsHandler $clientHandler,
        private readonly ImportTerminalsHandler $terminalHandler,
    ) {}

    #[Route('/clients', name: 'client_import_clients', methods: ['POST'])]
    #[IsGranted('client.import')]
    public function importClients(Request $request): JsonResponse
    {
        return $this->handleImport($request, $this->clientHandler);
    }

    #[Route('/clients/template', name: 'client_import_template', methods: ['GET'])]
    public function downloadClientsTemplate(): Response
    {
        $headers = ['ruc', 'nombre', 'direccion', 'email', 'telefono'];
        $content = implode(',', $headers) . "\n" . implode(',', ['12345678901', 'Ejemplo S.A.C.', 'Av. Ejemplo 123', 'contacto@ejemplo.com', '999888777']);

        return new Response(
            $content,
            Response::HTTP_OK,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="clientes_plantilla.csv"',
            ],
        );
    }

    #[Route('/terminals', name: 'client_import_terminals', methods: ['POST'])]
    #[IsGranted('client.import')]
    public function importTerminals(Request $request): JsonResponse
    {
        return $this->handleImport($request, $this->terminalHandler);
    }

    #[Route('/terminals/template', name: 'client_import_terminals_template', methods: ['GET'])]
    public function downloadTerminalsTemplate(): Response
    {
        $headers = ['ruc', 'serial', 'vendor', 'funcionality', 'type'];
        $content = implode(',', $headers) . "\n"
            . implode(',', ['12345678901', 'ATM-20240001', 'hyosung', 'dispenser', 'lobby']);

        return new Response(
            $content,
            Response::HTTP_OK,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="terminales_plantilla.csv"',
            ],
        );
    }

    private function handleImport(Request $request, ImportClientsHandler|ImportTerminalsHandler $handler): JsonResponse
    {
        $file = $request->files->get('file');
        if ($file === null) {
            return $this->json(['error' => 'Archivo no proporcionado'], Response::HTTP_BAD_REQUEST);
        }

        $extension = $file->getClientOriginalExtension();
        if (!in_array($extension, ['csv', 'xlsx'], true)) {
            return $this->json(['error' => 'Formato no soportado. Use CSV o XLSX.'], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->doImport($handler, $file->getPathname(), (bool) $request->request->get('dry_run', false));

        return $this->json([
            'data' => [
                'imported' => $result->imported,
                'errors'   => $result->errors,
                'skipped'  => $result->skipped,
                'details'  => $result->errorDetail,
            ],
        ]);
    }

    private function doImport(
        ImportClientsHandler|ImportTerminalsHandler $handler,
        string $filePath,
        bool $dryRun,
    ): ImportResult {
        if ($handler instanceof ImportClientsHandler) {
            return ($handler)(new ImportClientsCommand(filePath: $filePath, dryRun: $dryRun));
        }

        return ($handler)(new ImportTerminalsCommand(filePath: $filePath, dryRun: $dryRun));
    }
}
