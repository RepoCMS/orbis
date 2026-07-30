<?php

declare(strict_types=1);

namespace App\Client\Presentation\Http;

use App\Client\Application\Command\ImportClients\ImportClientsCommand;
use App\Client\Application\Command\ImportClients\ImportClientsHandler;
use App\Client\Application\Command\ImportTerminals\ImportTerminalsCommand;
use App\Client\Application\Command\ImportTerminals\ImportTerminalsHandler;
use App\Shared\Application\Import\ImportResult;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client/import')]
#[IsGranted('client.import')]
final class ClientImportController extends AbstractController
{
    public function __construct(
        private readonly ImportClientsHandler $clientHandler,
        private readonly ImportTerminalsHandler $terminalHandler,
    ) {}

    #[Route('', name: 'client_import', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('client/import.html.twig');
    }

    #[Route('/clients', name: 'client_import_clients_web', methods: ['POST'])]
    public function importClients(Request $request): Response
    {
        return $this->handleImport(
            $request,
            $this->clientHandler,
            'client_import',
            ImportClientsCommand::class,
        );
    }

    #[Route('/terminals', name: 'client_import_terminals_web', methods: ['POST'])]
    public function importTerminals(Request $request): Response
    {
        return $this->handleImport(
            $request,
            $this->terminalHandler,
            'client_import',
            ImportTerminalsCommand::class,
        );
    }

    private function handleImport(
        Request $request,
        ImportClientsHandler|ImportTerminalsHandler $handler,
        string $redirectRoute,
        string $commandClass,
    ): Response {
        $file = $request->files->get('file');
        if ($file === null) {
            $this->addFlash('error', 'No se ha proporcionado ningún archivo.');
            return $this->redirectToRoute($redirectRoute);
        }

        $extension = $file->getClientOriginalExtension();
        if (!in_array($extension, ['csv', 'xlsx'], true)) {
            $this->addFlash('error', 'Formato no soportado. Use archivos CSV o XLSX.');
            return $this->redirectToRoute($redirectRoute);
        }

        $dryRun = (bool) $request->request->get('dry_run', false);

        try {
            if ($handler instanceof ImportClientsHandler) {
                $result = $handler(new ImportClientsCommand(
                    filePath: $file->getPathname(),
                    dryRun: $dryRun,
                ));
            } else {
                $result = $handler(new ImportTerminalsCommand(
                    filePath: $file->getPathname(),
                    dryRun: $dryRun,
                ));
            }

            if ($dryRun) {
                $this->addFlash('info', sprintf(
                    'Previsualización: %d importados, %d errores, %d omitidos.',
                    $result->imported,
                    $result->errors,
                    $result->skipped,
                ));
            } else {
                $this->addFlash('success', sprintf(
                    'Importación completada: %d registros importados, %d errores, %d omitidos.',
                    $result->imported,
                    $result->errors,
                    $result->skipped,
                ));
            }

            if (!empty($result->errorDetail)) {
                $this->addFlash('warning', 'Algunas filas presentaron errores. Revise el detalle.');
            }
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Error al procesar la importación: ' . $e->getMessage());
        }

        return $this->redirectToRoute($redirectRoute);
    }
}
