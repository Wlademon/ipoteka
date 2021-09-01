<?php

namespace App\Drivers\Traits;

use Ajaxray\PHPWatermark\Watermark;
use App\Models\Contract;
use App\Services\WatermarkService;
use Barryvdh\DomPDF\Facade as PDF;
use RuntimeException;
use setasign\Fpdi\FpdfTpl;
use setasign\Fpdi\Fpdi;

/**
 * Trait PrintPdfTrait
 * @package App\Drivers\Traits
 */
trait PrintPdfTrait
{

    /**
     * @param $path
     * @return string
     */
    public static function generateBase64(string $path): string
    {
        return base64_encode(file_get_contents($path));
    }

    /**
     * @param Contract $contract
     * @param $sample boolean
     * @param string $filename PDF filename with path
     * @return string
     */
    protected static function generatePdf(Contract $contract, bool $sample = false, string $filename = ''): string
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
                throw new RuntimeException('Directory "%s" was not created :' . $dir);
            }
        }
        $pdf->save($filename);

        return $filename;
    }

    /**
     * @param string $path
     * @param string $content
     * @return bool
     */
    protected static function saveFileFromContent(string $path, string $content): bool
    {
        $dir = substr($path, 0, strrpos($path, DIRECTORY_SEPARATOR));
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new RuntimeException("Directory '$dir' was not created");
            }
        }
        return (bool)file_put_contents($path, $content);
    }

    /**
     * @param $pathPdf
     * @param $pathWatermark
     * @param $resultPath
     * @return string
     * @throws \Exception
     */
    public static function setWatermark(string $pathPdf, string $pathWatermark, string $resultPath): string
    {
        $watermark = new WatermarkService($pathPdf, true);
        $watermark->setStyle(Watermark::STYLE_IMG_COLORLESS)->setOpacity(.4);
        $pdf = $watermark->withImage($pathWatermark, $resultPath);

        if (!$pdf) {
            throw new RuntimeException('Not install imagic or not set rules for pdf');
        }

        return $pdf;
    }
}
