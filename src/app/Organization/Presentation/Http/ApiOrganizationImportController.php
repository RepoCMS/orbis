<?php

declare(strict_types=1);

namespace App\Organization\Presentation\Http;

use App\Organization\Application\Command\ImportEmployees\ImportEmployeesCommand;
use App\Organization\Application\Command\ImportEmployees\ImportEmployeesHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/organization/import')]
final class ApiOrganizationImportController extends AbstractController
{
    public function __construct(
        private readonly ImportEmployeesHandler $handler,
    ) {}

    #[Route('/employees', name: 'organization_import_employees', methods: ['POST'])]
    #[IsGranted('organization.import')]
    public function importEmployees(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if ($file === null) {
            return $this->json(['error' => 'Archivo no proporcionado'], Response::HTTP_BAD_REQUEST);
        }

        $extension = $file->getClientOriginalExtension();
        if (!in_array($extension, ['csv', 'xlsx'], true)) {
            return $this->json(['error' => 'Formato no soportado. Use CSV o XLSX.'], Response::HTTP_BAD_REQUEST);
        }

        $result = ($this->handler)(new ImportEmployeesCommand(
            filePath: $file->getPathname(),
            dryRun: (bool) $request->request->get('dry_run', false),
        ));

        return $this->json([
            'data' => [
                'imported' => $result->imported,
                'errors'   => $result->errors,
                'skipped'  => $result->skipped,
                'details'  => $result->errorDetail,
            ],
        ]);
    }

    #[Route('/employees/template', name: 'organization_import_employees_template', methods: ['GET'])]
    public function downloadTemplate(): Response
    {
        $headers = ['identificacion', 'nombres', 'apellidos', 'email', 'telefono', 'fecha_ingreso', 'perfil'];
        $content = implode(',', $headers) . "\n"
            . implode(',', ['1234567890', 'Juan', 'Perez', 'juan.perez@ejemplo.com', '0999999999', '2026-01-15', 'technician_field']);

        return new Response(
            $content,
            Response::HTTP_OK,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="empleados_plantilla.csv"',
            ],
        );
    }
}
