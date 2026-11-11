<?php

namespace App\Support;

/**
 * Membuat berkas Word (.docx) dari struktur laporan sederhana.
 */
class DocxReport
{
    private const TABLE_WIDTH = 15000;

    public function build(array $report): string
    {
        $columns = array_values($report['columns'] ?? []);
        $rows = array_values($report['rows'] ?? []);
        $meta = $report['meta'] ?? [];

        $body = '';
        $body .= $this->paragraph((string) ($report['title'] ?? 'Laporan'), [
            'bold' => true,
            'size' => 32,
            'align' => 'center',
            'after' => 80,
        ]);

        if (! empty($report['subtitle'])) {
            $body .= $this->paragraph((string) $report['subtitle'], [
                'bold' => true,
                'size' => 26,
                'align' => 'center',
                'after' => 160,
            ]);
        }

        if ($meta !== []) {
            $metaLines = [];

            foreach ($meta as $label => $value) {
                $metaLines[] = $label.': '.$value;
            }

            foreach ($metaLines as $line) {
                $body .= $this->paragraph($line, [
                    'size' => 18,
                    'after' => 20,
                ]);
            }

            $body .= $this->paragraph('', ['size' => 12, 'after' => 60]);
        }

        $body .= $this->table($columns, $rows);

        $section = '<w:sectPr>'
            .'<w:pgSz w:w="16838" w:h="11906" w:orient="landscape"/>'
            .'<w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720" w:header="360" w:footer="360" w:gutter="0"/>'
            .'</w:sectPr>';

        $document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:body>'.$body.$section.'</w:body>'
            .'</w:document>';

        $zip = new ZipBuilder;
        $zip->addFile('[Content_Types].xml', $this->contentTypes());
        $zip->addFile('_rels/.rels', $this->rootRelations());
        $zip->addFile('word/document.xml', $document);
        $zip->addFile('word/styles.xml', $this->styles());
        $zip->addFile('word/_rels/document.xml.rels', $this->documentRelations());

        return $zip->getContents();
    }

    /**
     * @param  array<int, array{label: string, width?: float, align?: string}>  $columns
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function table(array $columns, array $rows): string
    {
        $columnCount = count($columns);

        if ($columnCount === 0) {
            return '';
        }

        $widths = [];
        $widthTotal = 0.0;

        foreach ($columns as $column) {
            $width = max(4.0, (float) ($column['width'] ?? 12));
            $widths[] = $width;
            $widthTotal += $width;
        }

        $widthTwips = array_map(
            fn (float $width) => (int) round(($width / $widthTotal) * self::TABLE_WIDTH),
            $widths
        );

        $xml = '<w:tbl>'
            .'<w:tblPr>'
            .'<w:tblW w:w="'.self::TABLE_WIDTH.'" w:type="dxa"/>'
            .'<w:tblLayout w:type="fixed"/>'
            .'<w:tblBorders>'
            .'<w:top w:val="single" w:sz="4" w:space="0" w:color="9CA3AF"/>'
            .'<w:left w:val="single" w:sz="4" w:space="0" w:color="9CA3AF"/>'
            .'<w:bottom w:val="single" w:sz="4" w:space="0" w:color="9CA3AF"/>'
            .'<w:right w:val="single" w:sz="4" w:space="0" w:color="9CA3AF"/>'
            .'<w:insideH w:val="single" w:sz="4" w:space="0" w:color="9CA3AF"/>'
            .'<w:insideV w:val="single" w:sz="4" w:space="0" w:color="9CA3AF"/>'
            .'</w:tblBorders>'
            .'<w:tblCellMar>'
            .'<w:top w:w="50" w:type="dxa"/><w:left w:w="70" w:type="dxa"/>'
            .'<w:bottom w:w="50" w:type="dxa"/><w:right w:w="70" w:type="dxa"/>'
            .'</w:tblCellMar>'
            .'</w:tblPr>'
            .'<w:tblGrid>';

        foreach ($widthTwips as $width) {
            $xml .= '<w:gridCol w:w="'.$width.'"/>';
        }

        $xml .= '</w:tblGrid>';

        // Baris judul kolom.
        $xml .= '<w:tr><w:trPr><w:tblHeader/></w:trPr>';

        foreach ($columns as $index => $column) {
            $xml .= $this->cell(
                (string) ($column['label'] ?? ''),
                $widthTwips[$index],
                ['bold' => true, 'size' => 17, 'align' => 'center', 'shade' => 'E5E7EB']
            );
        }

        $xml .= '</w:tr>';

        if ($rows === []) {
            $xml .= '<w:tr><w:tc><w:tcPr><w:tcW w:w="'.self::TABLE_WIDTH.'" w:type="dxa"/>'
                .'<w:gridSpan w:val="'.$columnCount.'"/></w:tcPr>'
                .$this->paragraph('Tidak ada data yang sesuai dengan filter.', ['size' => 17, 'align' => 'center'])
                .'</w:tc></w:tr>';
        }

        foreach ($rows as $row) {
            $xml .= '<w:tr>';

            foreach ($columns as $index => $column) {
                $value = $row[$index] ?? '';
                $align = $column['align'] ?? 'left';

                $xml .= $this->cell((string) $value, $widthTwips[$index], [
                    'size' => 16,
                    'align' => $align,
                ]);
            }

            $xml .= '</w:tr>';
        }

        return $xml.'</w:tbl>';
    }

    /**
     * @param  array{bold?: bool, size?: int, align?: string, shade?: string}  $options
     */
    private function cell(string $text, int $width, array $options = []): string
    {
        $shade = isset($options['shade'])
            ? '<w:shd w:val="clear" w:color="auto" w:fill="'.$options['shade'].'"/>'
            : '';

        return '<w:tc><w:tcPr><w:tcW w:w="'.$width.'" w:type="dxa"/>'.$shade.'</w:tcPr>'
            .$this->paragraph($text, $options)
            .'</w:tc>';
    }

    /**
     * @param  array{bold?: bool, size?: int, align?: string, after?: int}  $options
     */
    private function paragraph(string $text, array $options = []): string
    {
        $size = (int) ($options['size'] ?? 20);
        $align = $options['align'] ?? 'left';
        $after = (int) ($options['after'] ?? 0);

        $properties = '<w:pPr>'
            .'<w:spacing w:after="'.$after.'"/>'
            .'<w:jc w:val="'.$align.'"/>'
            .'</w:pPr>';

        $runProperties = '<w:rPr>'
            .(! empty($options['bold']) ? '<w:b/>' : '')
            .'<w:sz w:val="'.$size.'"/><w:szCs w:val="'.$size.'"/>'
            .'</w:rPr>';

        $escaped = $this->escape($text);
        $escaped = str_replace(["\r\n", "\n", "\r"], '</w:t><w:br/><w:t xml:space="preserve">', $escaped);

        return '<w:p>'.$properties.'<w:r>'.$runProperties
            .'<w:t xml:space="preserve">'.$escaped.'</w:t>'
            .'</w:r></w:p>';
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            .'</Types>';
    }

    private function rootRelations(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            .'</Relationships>';
    }

    private function documentRelations(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:docDefaults>'
            .'<w:rPrDefault><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:eastAsia="Calibri" w:cs="Calibri"/>'
            .'<w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr></w:rPrDefault>'
            .'<w:pPrDefault><w:pPr><w:spacing w:after="0"/></w:pPr></w:pPrDefault>'
            .'</w:docDefaults>'
            .'<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/></w:style>'
            .'</w:styles>';
    }

    private function escape(mixed $value): string
    {
        $value = (string) $value;
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? '';

        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
