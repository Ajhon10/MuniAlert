<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;

class StatisticsExport
{
    public function create(array $data, string $format): string
    {
        $directory = storage_path('app/private/statistics-exports');
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Unable to create the statistics export directory.');
        }
        $base = tempnam($directory, 'statistics-');
        if ($base === false) {
            throw new \RuntimeException('Unable to create the statistics export.');
        }
        $image = $base.'.png';
        try {
            $this->chart($data, $image);
            if ($format === 'pdf') {
                $options = new Options;
                $options->setIsRemoteEnabled(false);
                $options->setIsPhpEnabled(false);
                $options->setIsJavascriptEnabled(false);
                $options->setChroot($directory);
                $pdf = new Dompdf($options);
                $pdf->loadHtml(view('exports.statistics', ['data' => $data, 'chart' => 'data:image/png;base64,'.base64_encode(file_get_contents($image))])->render());
                $pdf->setPaper('A4', 'landscape');
                $pdf->render();

                return $pdf->output();
            }
            if ($format === 'docx') {
                Settings::setOutputEscapingEnabled(true);
                $word = new PhpWord;
                $word->setDefaultFontName('Arial');
                $word->setDefaultFontSize(9);
                $section = $word->addSection(['orientation' => 'landscape', 'marginLeft' => 600, 'marginRight' => 600, 'marginTop' => 600, 'marginBottom' => 600]);
                $section->addText('MuniAlert - Incident statistics', ['bold' => true, 'size' => 18]);
                $section->addText($this->caption($data));
                $section->addText($data['date_basis']);
                $section->addText('Total reports: '.$data['total'].'. Counts represent reports, not confirmed casualties or unique incidents.');
                $section->addImage($image, ['width' => 680, 'height' => 300]);
                $table = $section->addTable(['borderSize' => 4, 'borderColor' => 'CCDCD4', 'cellMargin' => 70]);
                foreach ($this->matrix($data) as $index => $row) {
                    $table->addRow(null, ['tblHeader' => $index === 0]);
                    foreach ($row as $cell) {
                        $table->addCell()->addText((string) $cell, ['bold' => $index === 0, 'size' => 8]);
                    }
                }
                IOFactory::createWriter($word, 'Word2007')->save($base);
            } else {
                $book = new Spreadsheet;
                $sheet = $book->getActiveSheet()->setTitle('Period statistics');
                foreach ([['MuniAlert - Incident statistics'], [$this->caption($data)], [$data['date_basis']], ['Total reports', $data['total']], ['Counts describe reports, not confirmed casualties or unique incidents.']] as $row => $cells) {
                    $this->cells($sheet, $row + 1, $cells);
                }
                foreach ($this->matrix($data) as $row => $cells) {
                    $this->cells($sheet, $row + 7, $cells);
                }
                $sheet->freezePane('B8');
                $lastColumn = Coordinate::stringFromColumnIndex(count($this->matrix($data)[0]));
                $sheet->setAutoFilter('A7:'.$lastColumn.(7 + count($data['periods'])));
                $sheet->getStyle('A7:'.$lastColumn.'7')->getFont()->setBold(true);
                foreach ($sheet->getColumnIterator() as $column) {
                    $sheet->getColumnDimension($column->getColumnIndex())->setWidth(20);
                }
                $sheet->getStyle('A1:'.$lastColumn.'7')->getAlignment()->setWrapText(true);
                $sheet->getRowDimension(3)->setRowHeight(36);
                $sheet->getRowDimension(7)->setRowHeight(42);
                $totals = $book->createSheet()->setTitle('Category totals');
                $this->cells($totals, 1, ['Hazard category', 'Reports']);
                foreach ($data['categories'] as $index => $category) {
                    $this->cells($totals, $index + 2, [$category['name'], $category['total']]);
                }
                $totals->getColumnDimension('A')->setWidth(32);
                $totals->getColumnDimension('B')->setWidth(15);
                $drawing = new Drawing;
                $drawing->setName('Report trend by category');
                $drawing->setDescription($this->caption($data));
                $drawing->setPath($image)->setHeight(360)->setCoordinates('D2')->setWorksheet($totals);
                (new Xlsx($book))->save($base);
                $book->disconnectWorksheets();
            }

            return file_get_contents($base);
        } finally {
            foreach ([$image, $base] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }
    }

    private function caption(array $data): string
    {
        return ucfirst($data['group']).' groups, '.$data['from'].' to '.$data['to'].' (Asia/Manila); '.$data['basis'].' date basis.';
    }

    private function matrix(array $data): array
    {
        return [['Period starting (PHT)', 'Total reports', 'Known incident time', ...array_column($data['categories'], 'name')],
            ...array_map(fn ($row) => [$row['period'], $row['total'], $row['known_time'], ...array_map(fn ($category) => $row['categories'][$category['id']] ?? 0, $data['categories'])], $data['periods'])];
    }

    private function cells(Worksheet $sheet, int $row, array $cells): void
    {
        foreach ($cells as $index => $cell) {
            $sheet->setCellValueExplicit([$index + 1, $row], $cell, is_int($cell) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING);
        }
    }

    private function chart(array $data, string $path): void
    {
        $canvas = imagecreatetruecolor(1400, 620);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $ink = imagecolorallocate($canvas, 33, 65, 52);
        imagefill($canvas, 0, 0, $white);
        $palette = ['23674F', '3769A0', 'B15C31', '7A5594', '9B6A13', '388284', '9E4C65', '526C37', '675D4D', '607992'];
        $colors = array_map(fn ($hex) => imagecolorallocate($canvas, hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))), $palette);
        $max = (int) ceil(max(1, ...array_column($data['periods'], 'total')) / 4) * 4;
        $width = 1280 / max(1, count($data['periods']));
        $barWidth = max(1, min(80, $width - 3));
        $font = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf');
        for ($i = 0; $i <= 4; $i++) {
            $y = 440 - $i * 90;
            imageline($canvas, 70, $y, 1350, $y, imagecolorallocate($canvas, 220, 230, 224));
            imagettftext($canvas, 16, 0, 8, $y + 6, $ink, $font, (string) ($max * $i / 4));
        }
        foreach ($data['periods'] as $index => $period) {
            $bottom = 440;
            foreach ($data['categories'] as $categoryIndex => $category) {
                $height = ($period['categories'][$category['id']] ?? 0) / $max * 360;
                if ($height > 0) {
                    $x = 70 + $index * $width + ($width - $barWidth) / 2;
                    imagefilledrectangle($canvas, (int) $x, (int) ($bottom - $height), (int) ($x + $barWidth), (int) $bottom, $colors[$categoryIndex % count($colors)]);
                }
                $bottom -= $height;
            }
            if ($index % max(1, (int) ceil(count($data['periods']) / 9)) === 0) {
                $label = $data['group'] === 'year' ? substr($period['period'], 0, 4) : substr($period['period'], 5);
                imagettftext($canvas, 16, 0, (int) (70 + ($index + .5) * $width - 30), 470, $ink, $font, $label);
            }
        }
        foreach ($data['categories'] as $index => $category) {
            $x = 30 + ($index % 4) * 340;
            $y = 490 + intdiv($index, 4) * 34;
            imagefilledrectangle($canvas, $x, $y, $x + 14, $y + 14, $colors[$index % count($colors)]);
            imagettftext($canvas, 15, 0, $x + 22, $y + 16, $ink, $font, mb_strimwidth($category['name'], 0, 24, '...').' ('.$category['total'].')');
        }
        imagepng($canvas, $path);
    }
}
