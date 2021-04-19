<?php

namespace App\Printers;

trait Base64Trait
{
    /**
     * @param string $content
     * @return bool
     */
    public function isBase64(string $content): bool
    {
        if (strpos($content, ';')) {
            $data = explode(';', $content);
            $data = explode(',', $data[1]);
            $content = trim($data[1]);
        }
        $content = str_replace(['\r'], '', $content);
        if (! preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $content)) {
            return false;
        }
        if (! $decoded = base64_decode($content, true)) {
            return false;
        }
        return base64_encode($decoded) === $content;
    }

    /**
     * @param string $encoded
     * @return string
     */
    public function base64Extension(string $encoded): string
    {
        $data = explode(';', $encoded);
        return preg_replace('{^(\w+):(\w+)/(\w+)}', '$3', $data[0]);
    }

    /**
     * @param string $filename
     * @return string
     */
    public function encodeFileBase64(string $filename) : string
    {
        if (! file_exists($filename) || ! is_readable($filename) || is_dir($filename)) {
            throw new \RuntimeException(sprintf('file \'%s\' not exists or not readable!', $filename));
        }
        return $this->isImage($filename)
            ? $this->encodeImageBase64($filename)
            : $this->encodeWithRFC2045($filename);
    }

    /**
     * @param string $filename
     * @return string
     */
    public function encodeImageBase64(string $filename): string
    {
        if (! file_exists($filename) || ! is_readable($filename) || is_dir($filename)) {
            throw new \RuntimeException(sprintf('file \'%s\' not exists or not readable!', $filename));
        }
        return sprintf(
            'data:%s;base64, %s',
            mime_content_type($filename),
            base64_encode(fread(fopen($filename, 'rb'), filesize($filename)))
        );
    }

    /**
     * @param string $filename
     * @return string
     */
    public function encodeWithRFC2045(string $filename): string
    {
        if (! file_exists($filename) || ! is_readable($filename) || is_dir($filename)) {
            throw new \RuntimeException(sprintf('file \'%s\' not exists or not readable!', $filename));
        }
        return base64_encode(fread(fopen($filename, 'rb'), filesize($filename)));
    }

    /**
     * @param string $filename
     * @return bool
     */
    protected function isImage(string $filename) : bool
    {
        return explode('/', mime_content_type($filename))[0]  === 'image';
    }
}
