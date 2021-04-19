<?php
declare(strict_types=1);

namespace App\Printers;

use App\Models\Contracts;
use Barryvdh\DomPDF\Facade as PDF;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

/**
 * Class PolicyPrinter
 * @package App\Services
 */
class PolicyPrinter
{
    use Base64Trait;

    /**
     * @var array
     */
    private $pdfPaths;

    /**
     * PolicyPrinter constructor.
     * @param array $pdfPaths
     */
    public function __construct(array $pdfPaths)
    {
        $this->pdfPaths = $pdfPaths;
        $this->pdfPaths = array_map(
            function ($path) {
                if (!is_dir($path)) {
                    mkdir($path, 0777, true);
                }
                return realpath($path);
            },
            $this->pdfPaths
        );
    }

    /**
     * @param Contracts $contract
     * @param string|null $filename
     * @return string
     */
    public function printPolicy(Contracts $contract, bool $sample = false): string
    {
        $template = mb_strtolower($contract->program->companyCode);
        $filename = $this->getFilenameWithDir($contract, $sample);
        PDF::setOptions(
            [
                'logOutputFile' => storage_path('logs/anti-mite-generate-pdf.htm'),
                'tempDir' => $this->pdfPaths['tmp'],
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'dpi' => 96,
            ]
        )->loadView("templates.$template", compact('contract', 'sample'))
            ->save($filename);

        return $filename;
    }

    /**
     * @param Contracts $contract
     * @param bool $sample
     * @return string
     */
    public function getFilenameWithDir(Contracts $contract, bool $sample = false): ?string
    {
        return $this->pdfPaths['path'] . '/' . $this->getFilename($contract, $sample);
    }

    /**
     * @param Contracts $contract
     * @param bool $sample
     * @return string
     */
    public function getFilename(Contracts $contract, bool $sample = false): string
    {
        return sha1($contract->id . $contract->number) . ($sample ? '_sample' : '') . '.pdf';
    }

    /**
     * @note предварительно настроить публичные ссылки и хранилище!!
     *
     * @param Contracts $contract
     * @param bool $sample
     * @return string|null
     */
    public function getPolicyLink(Contracts $contract, bool $sample = false): ?string
    {
        return $this->isPolicyExists($contract, $sample)
            ? Storage::url($this->getFilenameWithDir($contract, $sample))
            : null;
    }

    /**
     * @param Contracts $contract
     * @param bool $sample
     * @return bool
     */
    public function isPolicyExists(Contracts $contract, bool $sample = false): bool
    {
        return Storage::disk()
            ->exists(
                $this->getFilenameWithDir($contract, $sample)
            );
    }

    /**
     * @param Contracts $contract
     * @param bool $sample
     * @return string|null
     */
    public function getBase64Policy(Contracts $contract, bool $sample = false): ?string
    {
        $filename = $this->getFilenameWithDir($contract, $sample);
        return null !== $filename
            ? $this->encodeFileBase64($filename)
            : null;
    }
}
