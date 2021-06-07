<?php

namespace App\Drivers\Traits;

use App\Exceptions\Drivers\ReninsException;
use Storage;
use ZipArchive;

/**
 * Trait ZipTrait
 * @package App\Drivers\Traits
 */
trait ZipTrait
{
    protected static $tempPathZip = 'temp/zip/';

    /**
     * @param $file
     * @return string
     * @throws \Throwable
     */
    public static function unpackZip(string $file): string
    {
        $zip = new ZipArchive();
        throw_if(
            $zip->open(storage_path($file), ZipArchive::CHECKCONS) !== true,
            ReninsException::class,
            'Can\'t open ZIP-file!'
        );
        $dirFile = static::$tempPathZip . uniqid(date('Y_m_d_H_i_s'), false) . '/';
        Storage::makeDirectory($dirFile);
        $zip->extractTo(
            storage_path('app/' . $dirFile)
        );
        $zip->close();

        return $dirFile;
    }
}
