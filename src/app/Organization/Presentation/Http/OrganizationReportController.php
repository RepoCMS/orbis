<?php

declare(strict_types=1);

namespace App\Organization\Presentation\Http;

use App\Organization\Application\Report\ActivePersonnel\ActivePersonnelReportGenerator;
use App\Organization\Application\Report\Overtime\OvertimeReportGenerator;
use App\Organization\Application\Report\ScheduledVacations\ScheduledVacationsReportGenerator;
use App\Shared\Infrastructure\Export\ExcelExportService;
use App\Shared\Infrastructure\Export\PdfExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/organization/report')]
#[IsGranted('organization.report')]
final class OrganizationReportController extends AbstractController
{
    public function __construct(
        private readonly ActivePersonnelReportGenerator $activePersonnelReportGenerator,
        private readonly ScheduledVacationsReportGenerator $scheduledVacationsReportGenerator,
        private readonly OvertimeReportGenerator $overtimeReportGenerator,
        private readonly ExcelExportService $excelExport,
        private readonly PdfExportService $pdfExport,
    ) {}

    #[Route('/active-personnel.{_format}', name: 'organization_report_active_personnel', methods: ['GET'], requirements: ['_format' => 'xlsx|pdf'])]
    public function activePersonnel(Request $request, string $_format = 'xlsx'): Response
    {
        $data = $this->activePersonnelReportGenerator->generate($request->query->all());
        $title = $this->activePersonnelReportGenerator->getTitle();
        $headers = $this->activePersonnelReportGenerator->getHeaders();

        return match ($_format) {
            'pdf' => $this->pdfExport->export($title, $headers, $data),
            default => $this->excelExport->export($title, $headers, $data),
        };
    }

    #[Route('/scheduled-vacations.{_format}', name: 'organization_report_scheduled_vacations', methods: ['GET'], requirements: ['_format' => 'xlsx|pdf'])]
    public function scheduledVacations(Request $request, string $_format = 'xlsx'): Response
    {
        $data = $this->scheduledVacationsReportGenerator->generate($request->query->all());
        $title = $this->scheduledVacationsReportGenerator->getTitle();
        $headers = $this->scheduledVacationsReportGenerator->getHeaders();

        return match ($_format) {
            'pdf' => $this->pdfExport->export($title, $headers, $data),
            default => $this->excelExport->export($title, $headers, $data),
        };
    }

    #[Route('/overtime.{_format}', name: 'organization_report_overtime', methods: ['GET'], requirements: ['_format' => 'xlsx'])]
    public function overtime(Request $request, string $_format = 'xlsx'): Response
    {
        $data = $this->overtimeReportGenerator->generate($request->query->all());
        $title = $this->overtimeReportGenerator->getTitle();
        $headers = $this->overtimeReportGenerator->getHeaders();

        return $this->excelExport->export($title, $headers, $data);
    }
}
