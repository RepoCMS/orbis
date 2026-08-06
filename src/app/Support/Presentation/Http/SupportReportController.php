<?php

declare(strict_types=1);

namespace App\Support\Presentation\Http;

use App\Shared\Infrastructure\Export\ExcelExportService;
use App\Shared\Infrastructure\Export\PdfExportService;
use App\Support\Application\Report\Technician\TechnicianReportGenerator;
use App\Support\Application\Report\TicketsByClient\TicketsByClientReportGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/support/report')]
#[IsGranted('support.report')]
final class SupportReportController extends AbstractController
{
    public function __construct(
        private readonly TechnicianReportGenerator $technicianReportGenerator,
        private readonly TicketsByClientReportGenerator $ticketsByClientReportGenerator,
        private readonly ExcelExportService $excelExport,
        private readonly PdfExportService $pdfExport,
    ) {
    }

    #[Route('/technicians.{_format}', name: 'support_report_technicians', methods: ['GET'], requirements: ['_format' => 'xlsx|pdf'])]
    public function technicians(Request $request, string $_format = 'xlsx'): Response
    {
        $data = $this->technicianReportGenerator->generate($request->query->all());
        $title = $this->technicianReportGenerator->getTitle();
        $headers = $this->technicianReportGenerator->getHeaders();

        return match ($_format) {
            'pdf' => $this->pdfExport->export($title, $headers, $data),
            default => $this->excelExport->export($title, $headers, $data),
        };
    }

    #[Route('/tickets-by-client.{_format}', name: 'support_report_tickets_by_client', methods: ['GET'], requirements: ['_format' => 'xlsx'])]
    public function ticketsByClient(Request $request, string $_format = 'xlsx'): Response
    {
        $data = $this->ticketsByClientReportGenerator->generate($request->query->all());
        $title = $this->ticketsByClientReportGenerator->getTitle();
        $headers = $this->ticketsByClientReportGenerator->getHeaders();

        return $this->excelExport->export($title, $headers, $data);
    }
}
