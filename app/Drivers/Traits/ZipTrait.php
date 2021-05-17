<?php

namespace App\Drivers\Traits;

use App\Exceptions\Drivers\ReninsException;

trait ZipTrait
{
    protected static $tempPathZip = 'temp/zip/';

    public static function unpackZip($file)
    {
        $zip = new \ZipArchive();
        throw_if(!$zip->open(\Storage::path($file)), ReninsException::class, ['Can\'t open ZIP-file!']);
        $dirFile = static::$tempPathZip . uniqid(date('Y_m_d_H_i_s'), false) . '/';
        \Storage::makeDirectory($dirFile);
        $zip->extractTo(
            \Storage::path($dirFile)
        );
        $zip->close();

        return $dirFile;
    }
}
