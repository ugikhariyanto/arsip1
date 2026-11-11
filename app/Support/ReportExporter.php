<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class ReportExporter
{
    public function download(string $format, array $report): Response
    {
        $format = strtolower($format);
        $filename = $this->filename($report);

        return match ($format) {
            'xlsx', 'excel' => $this->binaryResponse(
                (new XlsxReport)->build($report),
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                $filename.'.xlsx'
            ),
            'docx', 'word', 'doc' => $this->binaryResponse(
                (new DocxReport)->build($report),
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                $filename.'.docx'
            ),
            'pdf' => Pdf::loadView('reports.pdf.document', ['report' => $report])
                ->setPaper('a4', $report['orientation'] ?? 'landscape')
                ->download($filename.'.pdf'),
            default => abort(404, 'Format laporan tidak dikenal.'),
        };
    }

    private function binaryResponse(string $contents, string $contentType, string $filename): Response
    {
        return response($contents, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($contents),
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    private function filename(array $report): string
    {
        $name = (string) ($report['filename'] ?? 'laporan');

        return preg_replace('/[^A-Za-z0-9._-]+/', '-', $name) ?: 'laporan';
    }
}
