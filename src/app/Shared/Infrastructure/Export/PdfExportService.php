<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Export;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PdfExportService
{
    /**
     * @param array<string, mixed>[] $data
     * @param string[]                $headers
     */
    public function export(string $title, array $headers, array $data): StreamedResponse
    {
        return new StreamedResponse(function () use ($title, $headers, $data) {
            $options = new Options();
            $options->set('isRemoteEnabled', false);
            $options->set('isHtml5ParserEnabled', true);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($this->buildHtml($title, $headers, $data));
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            $dompdf->stream($title . '.pdf', ['Attachment' => true]);
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment;filename="%s.pdf"', $title),
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * @param array<string, mixed>[] $data
     */
    private function buildHtml(string $title, array $headers, array $data): string
    {
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $html .= '<style>';
        $html .= 'body { font-family: sans-serif; font-size: 10px; }';
        $html .= 'h1 { font-size: 16px; color: #1e3585; margin-bottom: 10px; }';
        $html .= 'table { width: 100%; border-collapse: collapse; margin-top: 10px; }';
        $html .= 'th { background: #1e3585; color: white; padding: 6px 8px; text-align: left; font-size: 9px; }';
        $html .= 'td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; font-size: 9px; }';
        $html .= 'tr:nth-child(even) { background: #f9fafb; }';
        $html .= '</style></head><body>';
        $html .= '<h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>';
        $html .= '<table><thead><tr>';

        foreach ($headers as $header) {
            $html .= '<th>' . htmlspecialchars($header, ENT_QUOTES, 'UTF-8') . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $value) {
                $html .= '<td>' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table></body></html>';

        return $html;
    }
}
