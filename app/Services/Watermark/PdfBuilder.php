<?php

namespace App\Services\Watermark;

use Ajaxray\PHPWatermark\CommandBuilders\PDFCommandBuilder;

class PdfBuilder extends PDFCommandBuilder
{

    public $transparent = false;

    /**
     * Build the imagemagick shell command for watermarking with Image
     *
     * @param string $markerImage The image file to watermark with
     * @param string $output The watermarked output file
     * @param array $options
     * @return string
     */
    public function getImageMarkCommand($markerImage, $output, array $options)
    {
        [$source, $destination] = $this->prepareContext($output, $options);
        $marker = escapeshellarg($markerImage);

        $opacity = $this->getMarkerOpacity();
        $anchor = $this->getAnchor();
        $offset = $this->getImageOffset();
        $transparent = $this->getTransparent();

        return "convert $marker $transparent $opacity  miff:- | convert -density 100 $source null: - -$anchor -$offset -quality 100 -compose multiply -layers composite $destination";
    }

    protected function getTransparent(): string
    {
        if ($this->transparent) {
            return '-transparent "#ffffff"';
        }

        return '';
    }

    /**
     * @param $transparent mixed
     */
    public function setTransparent($is = true): void
    {
        $this->transparent = $is;
    }

    private function getMarkerOpacity(): string
    {
        $opacity = $this->getOpacity() * 100;

        return "-alpha set -channel A -evaluate set {$opacity}%";
    }
}
