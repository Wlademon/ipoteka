<?php

namespace App\Services;

use Ajaxray\PHPWatermark\CommandBuilders\AbstractCommandBuilder;
use Ajaxray\PHPWatermark\CommandBuilders\ImageCommandBuilder;
use Ajaxray\PHPWatermark\CommandBuilders\PDFCommandBuilder;
use Ajaxray\PHPWatermark\Watermark;
use App\Services\Watermark\PdfBuilder;

class WatermarkService extends Watermark
{
    public $transparent = false;

    public function __construct($source, $transparent = false)
    {
        $this->transparent = $transparent;
        parent::__construct($source);
    }

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
