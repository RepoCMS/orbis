<?php

declare(strict_types=1);

namespace App\Organization\Presentation\Http;

use App\Organization\Application\Command\ImportEmployees\ImportEmployeesCommand;
use App\Organization\Application\Command\ImportEmployees\ImportEmployeesHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/organization/import')]
#[IsGranted('organization.import')]
final class OrganizationImportController extends AbstractController
{
    public function __construct(
        private readonly ImportEmployeesHandler $handler,
    ) {}

    #[Route('', name: 'organization_import', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('organization/import.html.twig');
    }

    #[Route('/employees', name: 'organization_import_employees_web', methods: ['POST'])]
    public function importEmployees(Request $request): Response
    {
        $file = $request->files->get('file');
        if ($file === null) {
            $this->addFlash('error', 'No se ha proporcionado ningún archivo.');
            return $this->redirectToRoute('organization_import');
        }

        $extension = $file->getClientOriginalExtension();
        if (!in_array($extension, ['csv', 'xlsx'], true)) {
            $this->addFlash('error', 'Formato no soportado. Use archivos CSV o XLSX.');
            return $this->redirectToRoute('organization_import');
        }

        $dryRun = (bool) $request->request->get('dry_run', false);

        try {
            $result = ($this->handler)(new ImportEmployeesCommand(
                filePath: $file->getPathname(),
                dryRun: $dryRun,
            ));

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

        return $this->redirectToRoute('organization_import');
    }
}
