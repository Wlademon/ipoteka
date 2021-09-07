<?php

namespace App\Services;

use Ajaxray\PHPWatermark\CommandBuilders\AbstractCommandBuilder;
use Ajaxray\PHPWatermark\CommandBuilders\ImageCommandBuilder;
use Ajaxray\PHPWatermark\CommandBuilders\PDFCommandBuilder;
use Ajaxray\PHPWatermark\Watermark;
use App\Services\Watermark\PdfBuilder;

/**
 * Class WatermarkService
 *
 * @package App\Services
 */
class WatermarkService extends Watermark
{
    public $transparent = false;

    /**
     * Watermark constructor.
     *
     * @param string $source Source Image
     */
    public function __construct($source, $transparent = false)
    {
        $this->transparent = $transparent;
        parent::__construct($source);
    }

    /**
     * Factory for choosing CommandBuilder
     *
     * @param $sourcePath
     * @return CommandBuilders\ImageCommandBuilder|CommandBuilders\PDFCommandBuilder
     */
    protected function getCommandBuilder($sourcePath): AbstractCommandBuilder
    {
        $builder = parent::getCommandBuilder($sourcePath);
        if ($builder instanceof PDFCommandBuilder) {
            $builder = new PdfBuilder($sourcePath);
            $builder->setTransparent($this->transparent);

            return $builder;
        }

        return $builder;
    }
}
