<?php

namespace App\Support;

/**
 * Membuat berkas Excel (.xlsx) dari struktur laporan sederhana.
 *
 * Struktur laporan:
 * [
 *     'title' => 'JUDUL',
 *     'subtitle' => 'SUB JUDUL',
 *     'meta' => ['Label' => 'Nilai', ...],
 *     'columns' => [['label' => 'No', 'width' => 5, 'align' => 'center'], ...],
 *     'rows' => [['1', 'ATK-0001', ...], ...],
 * ]
 */
class XlsxReport
{
    public function build(array $report): string
    {
        $columns = array_values($report['columns'] ?? []);
        $columnCount = max(1, count($columns));
        $lastColumn = $this->columnLetter($columnCount);
        $rows = array_values($report['rows'] ?? []);
        $meta = $report['meta'] ?? [];

        $sheetRows = [];
        $merges = [];
        $rowNumber = 1;

        $title = trim((string) ($report['title'] ?? 'Laporan'));
        $sheetRows[] = $this->buildRow($rowNumber, [['value' => $title, 'style' => 1]]);
        $merges[] = 'A1:'.$lastColumn.'1';
        $rowNumber++;

        if (! empty($report['subtitle'])) {
            $sheetRows[] = $this->buildRow($rowNumber, [['value' => $report['subtitle'], 'style' => 2]]);
            $merges[] = 'A'.$rowNumber.':'.$lastColumn.$rowNumber;
            $rowNumber++;
        }

        foreach ($meta as $label => $value) {
            $sheetRows[] = $this->buildRow($rowNumber, [[
                'value' => $label.': '.$value,
                'style' => 3,
            ]]);
            $merges[] = 'A'.$rowNumber.':'.$lastColumn.$rowNumber;
            $rowNumber++;
        }

        // Satu baris kosong sebelum tabel.
        $sheetRows[] = '<row r="'.$rowNumber.'" ht="8" customHeight="1"/>';
        $rowNumber++;

        $headerRowNumber = $rowNumber;
        $headerCells = [];

        foreach ($columns as $index => $column) {
            $headerCells[] = [
                'value' => (string) ($column['label'] ?? ''),
                'style' => 4,
                'column' => $index + 1,
            ];
        }

        $sheetRows[] = $this->buildRow($rowNumber, $headerCells, 30);
        $rowNumber++;

        foreach ($rows as $row) {
            $cells = [];

            foreach ($columns as $index => $column) {
                $value = $row[$index] ?? '';
                $align = $column['align'] ?? 'left';
                $style = match ($align) {
                    'center' => 6,
                    'right' => 7,
                    default => 5,
                };

                $cells[] = [
                    'value' => $value,
                    'style' => $style,
                    'column' => $index + 1,
                ];
            }

            $sheetRows[] = $this->buildRow($rowNumber, $cells);
            $rowNumber++;
        }

        $lastRowNumber = max($headerRowNumber, $rowNumber - 1);

        if ($rows === []) {
            $sheetRows[] = $this->buildRow($rowNumber, [[
                'value' => 'Tidak ada data yang sesuai dengan filter.',
                'style' => 8,
            ]]);
            $merges[] = 'A'.$rowNumber.':'.$lastColumn.$rowNumber;
            $lastRowNumber = $rowNumber;
        }

        $columnXml = '';

        foreach ($columns as $index => $column) {
            $number = $index + 1;
            $width = max(5, (float) ($column['width'] ?? 14));
            $columnXml .= '<col min="'.$number.'" max="'.$number.'" width="'.$this->number($width).'" customWidth="1"/>';
        }

        $freezeRow = $headerRowNumber + 1;
        $autoFilter = 'A'.$headerRowNumber.':'.$lastColumn.$lastRowNumber;

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView tabSelected="1" workbookViewId="0">'
            .'<pane ySplit="'.$headerRowNumber.'" topLeftCell="A'.$freezeRow.'" activePane="bottomLeft" state="frozen"/>'
            .'</sheetView></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="15"/>'
            .'<cols>'.$columnXml.'</cols>'
            .'<sheetData>'.implode('', $sheetRows).'</sheetData>'
            .($merges !== [] ? '<mergeCells count="'.count($merges).'">'.implode('', array_map(fn ($range) => '<mergeCell ref="'.$range.'"/>', $merges)).'</mergeCells>' : '')
            .'<autoFilter ref="'.$autoFilter.'"/>'
            .'<pageMargins left="0.3" right="0.3" top="0.4" bottom="0.4" header="0.2" footer="0.2"/>'
            .'<pageSetup orientation="landscape" paperSize="9" fitToWidth="1" fitToHeight="0"/>'
            .'</worksheet>';

        $zip = new ZipBuilder;
        $zip->addFile('[Content_Types].xml', $this->contentTypes());
        $zip->addFile('_rels/.rels', $this->rootRelations());
        $zip->addFile('xl/workbook.xml', $this->workbook($report['sheet_name'] ?? 'Laporan'));
        $zip->addFile('xl/_rels/workbook.xml.rels', $this->workbookRelations());
        $zip->addFile('xl/styles.xml', $this->styles());
        $zip->addFile('xl/worksheets/sheet1.xml', $sheet);

        return $zip->getContents();
    }

    /**
     * @param  array<int, array{value: mixed, style: int, column?: int}>  $cells
     */
    private function buildRow(int $rowNumber, array $cells, ?int $height = null): string
    {
        $attributes = ' r="'.$rowNumber.'"';

        if ($height !== null) {
            $attributes .= ' ht="'.$height.'" customHeight="1"';
        }

        $xml = '<row'.$attributes.'>';

        foreach ($cells as $index => $cell) {
            $columnNumber = $cell['column'] ?? ($index + 1);
            $reference = $this->columnLetter($columnNumber).$rowNumber;
            $value = $cell['value'];
            $style = (int) $cell['style'];

            if (is_int($value) || is_float($value)) {
                $xml .= '<c r="'.$reference.'" s="'.$style.'"><v>'.$this->number($value).'</v></c>';
                continue;
            }

            $xml .= '<c r="'.$reference.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'
                .$this->escape($value)
                .'</t></is></c>';
        }

        return $xml.'</row>';
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private function rootRelations(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbook(string $sheetName): string
    {
        $sheetName = $this->escape(mb_substr($sheetName, 0, 31));

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.$sheetName.'" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private function workbookRelations(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0"/></numFmts>'
            .'<fonts count="5">'
            .'<font><sz val="10"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="14"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="12"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            .'<font><i/><sz val="10"/><color rgb="FF6B7280"/><name val="Calibri"/></font>'
            .'</fonts>'
            .'<fills count="3">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF1F2937"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="2">'
            .'<border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border>'
            .'<left style="thin"><color rgb="FFD1D5DB"/></left>'
            .'<right style="thin"><color rgb="FFD1D5DB"/></right>'
            .'<top style="thin"><color rgb="FFD1D5DB"/></top>'
            .'<bottom style="thin"><color rgb="FFD1D5DB"/></bottom>'
            .'<diagonal/>'
            .'</border>'
            .'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="9">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="top" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="top" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="4" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    private function columnLetter(int $number): string
    {
        $letters = '';

        while ($number > 0) {
            $number--;
            $letters = chr(65 + ($number % 26)).$letters;
            $number = intdiv($number, 26);
        }

        return $letters;
    }

    private function escape(mixed $value): string
    {
        $value = (string) $value;
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? '';

        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function number(float|int $value): string
    {
        if (is_int($value) || floor($value) === $value) {
            return (string) (int) $value;
        }

        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
    }
}
