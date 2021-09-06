<?php
declare(strict_types=1);

namespace App\Printers;

use App\Drivers\DriverInterface;
use App\Drivers\OutPrintDriverInterface;
use App\Models\Contract;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Facades\Storage;

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
                    if (!mkdir($path, 0777, true) && !is_dir($path)) {
                        throw new \RuntimeException(
                            sprintf('Directory "%s" was not created', $path)
                        );
                    }
                }
                return realpath($path);
            },
            $this->pdfPaths
        );
    }

    /**
     * @param Contract $contract
     * @param string|null $filename
     * @return string
     */
    protected function printPolicy(Contract $contract, bool $sample = false): string
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

    public function print(DriverInterface $driver, Contract $contract, bool $sample = false): array
    {
        if (!$this->allPolicesExist($driver, $contract, $sample)) {
            $this->printPolicyFromDriver($driver, $contract, $sample);
        }

        return $this->allPolicesFiles($driver, $contract, $sample);
    }

    /**
     * @param  DriverInterface  $driver
     * @param  Contract         $contract
     * @param  bool             $sample
     *
     * @return array|string[]
     */
    protected function printPolicyFromDriver(DriverInterface $driver, Contract $contract, bool $sample = false)
    {
        if ($driver instanceof OutPrintDriverInterface) {
            $filesBase64 = $driver->printPolicy($contract, $sample, false);
            $files = [];
            foreach ($filesBase64 as $suffix => $item) {
                file_put_contents($this->getFilenameWithDir($contract, $sample, $suffix), $item);
                $files[] = $this->getFilenameWithDir($contract, $sample, $suffix);
            }
        } else {
            $files = [$this->printPolicy($contract, $sample)];
        }

        return $files;
    }

    protected function allPolicesExist(DriverInterface $driver, Contract $contract, bool $sample = false): bool
    {
        $suffixes = null;
        if ($driver instanceof OutPrintDriverInterface) {
            $suffixes = $driver->getPoliceIds($contract);
        }

        foreach ((array)$suffixes as $suffix) {
            if (!$this->isPolicyExists($contract, $sample, $suffix)) {
                return false;
            }
        }

        return true;
    }

    protected function allPolicesFiles(DriverInterface $driver, Contract $contract, bool $sample = false): array
    {
        $suffixes = null;
        if ($driver instanceof OutPrintDriverInterface) {
            $suffixes = $driver->getPoliceIds($contract);
        }

        $files = [];

        foreach ((array)$suffixes as $suffix) {
            $files[] = $this->getBase64Policy($contract, $sample, $suffix);
        }

        return array_filter($files);
    }

    /**
     * @param Contract $contract
     * @param bool $sample
     * @return string
     */
    public function getFilenameWithDir(Contract $contract, bool $sample = false, ?string $suffix = null): ?string
    {
        return $this->pdfPaths['path'] . '/' . $this->getFilename($contract, $sample, $suffix);
    }

    /**
     * @param Contract $contract
     * @param bool $sample
     * @return string
     */
    public function getFilename(Contract $contract, bool $sample = false, ?string $suffix = null): string
    {
        return sha1($contract->id . $contract->number) . ($suffix ? "_$suffix" : '') . ($sample ? '_sample' : '') . '.pdf';
    }

    /**
     * @note предварительно настроить публичные ссылки и хранилище!!
     *
     * @param Contract $contract
     * @param bool $sample
     * @return string|null
     */
    public function getPolicyLink(Contract $contract, bool $sample = false, ?string $suffix = null): ?string
    {
        return $this->isPolicyExists($contract, $sample)
            ? Storage::url($this->getFilenameWithDir($contract, $sample, $suffix))
            : null;
    }

    /**
     * @param Contract $contract
     * @param bool $sample
     * @return bool
     */
    public function isPolicyExists(Contract $contract, bool $sample = false, ?string $suffix = null): bool
    {
        return Storage::disk()->exists($this->getFilenameWithDir($contract, $sample, $suffix));
    }

    /**
     * @param Contract $contract
     * @param bool $sample
     * @return string|null
     */
    public function getBase64Policy(Contract $contract, bool $sample = false, ?string $suffix = null): ?string
    {
        $filename = $this->getFilenameWithDir($contract, $sample, $suffix);
        return null !== $filename
            ? $this->encodeFileBase64($filename)
            : null;
    }
}
