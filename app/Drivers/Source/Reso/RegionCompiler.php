<?php

namespace App\Drivers\Source\Reso;

use App\Services\HttpClientService;
use Illuminate\Support\Str;

class RegionCompiler
{
    const U_REGION = '23';
    const U_REGION_ID = 230;
    const OU_REGION_ID = 23;
    const OTHER_REGION = 300;
    const U_CITIES = [
        'новороссийск',
        'анапа',
        'геленджик',
        'туапсе'
    ];

    const CACHE_TLL = 300;

    const URL = '/policy/v1/tm/getRegionRef';

    protected HttpClientService $client;


    public function __construct(HttpClientService $client)
    {
        $this->client = $client;
    }

    protected function getRegions(): array
    {
        $key = base64_encode(static::class . __FUNCTION__);
        $regions = \Cache::get($key);
        if (!$regions) {
            $regions = $this->getApiRegions();
            \Cache::set($key, $regions, static::CACHE_TLL);
        }

        return $regions;
    }

    protected function getApiRegions(): array
    {
        $result = $this->client->sendGetJson(self::URL);
        if (!$result) {
            throw new \Exception( 'Reso regions not get.');
        }

        return $result;
    }

    public function compile($kladr, $city)
    {
        $regions = $this->getRegions();
        if (Str::startsWith($kladr, self::U_REGION)) {
            if ($city && in_array(mb_strtolower($city), self::U_CITIES)) {
                return self::U_REGION_ID;
            }

            return self::OU_REGION_ID;
        }
        $regions = collect($regions);
        $actualRegion = $regions->first(function($region) use ($kladr) {
            ['ID' => $id] = $region;

            return $id == mb_substr($kladr, 0, 2);
        });

        if ($actualRegion) {
            return $actualRegion['ID'];
        }

        return self::OTHER_REGION;
    }
}
