<?php

namespace App\Drivers\Traits;

use Ajaxray\PHPWatermark\Watermark;
use App\Models\Contracts;
use App\Services\WatermarkService;
use Barryvdh\DomPDF\Facade as PDF;
use http\Exception\RuntimeException;
use setasign\Fpdi\FpdfTpl;
use setasign\Fpdi\Fpdi;

trait PrintPdfTrait
{
    use LoggerTrait;

    public static function generateBase64($path): string
    {
        return base64_encode(file_get_contents($path));
    }

    /**
     * @param Contracts $contract
     * @param $sample boolean
     * @param string $filename PDF filename with path
     * @return string
     */
    protected static function generatePdf(Contracts $contract, $sample = false, $filename = ''): string
    {
        $template = mb_strtolower($contract->program->programCode);

        $sportCats = [];

        /** @var PDF $pdf */
        $pdf = PDF::setOptions([
            'logOutputFile' => storage_path('logs/ns-generate-pdf.htm'),
            'tempDir' => storage_path(config('ns.pdf.tmp')),
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
        ])->loadView(
            "templates.$template",
            compact('contract', 'sample', 'sportCats')
        );

        if (!$filename) {
            $sampleText = $sample ? '_sample' : '';
            $filename = public_path() . '/' . config('ns.pdf.path') . sha1($contract->id . $contract->number) . $sampleText . '.pdf';
        }
        $dir = substr($filename, 0, strrpos($filename, DIRECTORY_SEPARATOR) + 1);
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created :' . $dir, $dir));
            }
        }
        $pdf->save($filename);

        return $filename;
    }

    protected static function saveFileFromContent(string $path, string $content): bool
    {
        $dir = substr($path, 0, strrpos($path, DIRECTORY_SEPARATOR));
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
            }
        }
        return (bool)file_put_contents($path, $content);
    }

    public static function setWatermark($pathPdf, $pathWatermark, $resultPath): string
    {
        $watermark = new WatermarkService($pathPdf, true);
        $watermark->setStyle(Watermark::STYLE_IMG_COLORLESS)->setOpacity(.4);
        $pdf = $watermark->withImage($pathWatermark, $resultPath);

        if (!$pdf) {
            self::abortLog('Not install imagic or not set rules for pdf', RuntimeException::class);
        }

        return $pdf;
    }
}
