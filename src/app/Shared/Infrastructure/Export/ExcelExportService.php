<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Export;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExcelExportService
{
    /**
     * @param array<string, mixed>[] $data
     * @param string[]                $headers
     */
    public function export(string $title, array $headers, array $data): StreamedResponse
    {
        return new StreamedResponse(function () use ($title, $headers, $data) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle($title);

            foreach ($headers as $col => $header) {
                $cell = $sheet->getCellByColumnAndRow($col + 1, 1);
                $cell->setValue($header);
                $cell->getStyle()->getFont()->setBold(true);
            }

            foreach ($data as $rowIdx => $row) {
                foreach ($row as $col => $value) {
                    $sheet->getCellByColumnAndRow($col + 1, $rowIdx + 2)->setValue($value);
                }
            }

            foreach ($headers as $col => $header) {
                $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1))->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => sprintf('attachment;filename="%s.xlsx"', $title),
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
