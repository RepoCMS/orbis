<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Http;

use App\Identity\Application\Report\SecurityEvents\SecurityEventsReportGenerator;
use App\Shared\Infrastructure\Export\ExcelExportService;
use App\Shared\Infrastructure\Export\PdfExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/identity/report')]
#[IsGranted('identity.report')]
final class IdentityReportController extends AbstractController
{
    public function __construct(
        private readonly SecurityEventsReportGenerator $securityEventsReportGenerator,
        private readonly ExcelExportService $excelExport,
        private readonly PdfExportService $pdfExport,
    ) {
    }

    #[Route('/security-events.{_format}', name: 'identity_report_security_events', methods: ['GET'], requirements: ['_format' => 'xlsx|pdf'])]
    public function securityEvents(Request $request, string $_format = 'xlsx'): Response
    {
        $data = $this->securityEventsReportGenerator->generate($request->query->all());
        $title = $this->securityEventsReportGenerator->getTitle();
        $headers = $this->securityEventsReportGenerator->getHeaders();

        return match ($_format) {
            'pdf' => $this->pdfExport->export($title, $headers, $data),
            default => $this->excelExport->export($title, $headers, $data),
        };
    }
}
