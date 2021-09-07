<?php

namespace App\Services\Watermark;

use Ajaxray\PHPWatermark\CommandBuilders\PDFCommandBuilder;

/**
 * Class PdfBuilder
 *
 * @package App\Services\Watermark
 */
class PdfBuilder extends PDFCommandBuilder
{

    public bool $transparent = false;

    /**
     * Build the imagemagick shell command for watermarking with Image
     *
     * @param string $markerImage The image file to watermark with
     * @param string $output The watermarked output file
     * @param array $options
     * @return string
     */
    public function getImageMarkCommand($markerImage, $output, array $options): string
    {
        [$source, $destination] = $this->prepareContext($output, $options);
        $marker = escapeshellarg($markerImage);

        $opacity = $this->getMarkerOpacity();
        $anchor = $this->getAnchor();
        $offset = $this->getImageOffset();
        $transparent = $this->getTransparent();

        return "convert $marker $transparent $opacity  miff:- | convert -density 100 $source null: - -$anchor -$offset -quality 100 -compose multiply -layers composite $destination";
    }

    /**
     * @return string
     */
    protected function getTransparent(): string
    {
        if ($this->transparent) {
            return '-transparent "#ffffff"';
        }

        return '';
    }

    /**
     * @param  bool  $isTransparent
     */
    public function setTransparent(bool $isTransparent = true): void
    {
        $this->transparent = $isTransparent;
    }

    /**
     * @return string
     */
    private function getMarkerOpacity(): string
    {
        $opacity = $this->getOpacity() * 100;

        return "-alpha set -channel A -evaluate set {$opacity}%";
    }
}
