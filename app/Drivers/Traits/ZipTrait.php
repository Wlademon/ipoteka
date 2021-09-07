<?php

namespace App\Drivers\Traits;

use App\Exceptions\Drivers\ReninsException;
use Illuminate\Support\Facades\Storage;
use Throwable;
use ZipArchive;

/**
 * Trait ZipTrait
 *
 * @package App\Drivers\Traits
 */
trait ZipTrait
{
    protected static string $tempPathZip = 'temp/zip/';

    /**
     * @param  string  $file
     *
     * @return string
     * @throws ReninsException
     */
    public static function unpackZip(string $file): string
    {
        $zip = new ZipArchive();
        if ($zip->open(storage_path($file), ZipArchive::CHECKCONS) !== true) {
            throw new ReninsException(__METHOD__, 'Can\'t open ZIP-file!');
        }
        $dirFile = static::$tempPathZip . uniqid(date('Y_m_d_H_i_s'), false) . '/';
        Storage::makeDirectory($dirFile);
        $zip->extractTo(
            storage_path('app/' . $dirFile)
        );
        $zip->close();

        return $dirFile;
    }
}
