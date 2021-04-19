<?php

namespace App\Drivers\Source\Renissans;

class DataCollector
{
    const PRODUCT_SCHEMAS = [
        [
            'minAge' => 0,
            'maxAge' => 17,
            'code' => 'DMS-PR1',
        ],
        [
            'minAge' => 18,
            'maxAge' => 200,
            'code' => 'DMS-PR1',
        ],
    ];

    public static function choice(string $code, int $age, ?string &$ageGroup = null): ?string
    {
        $schema = self::PRODUCT_SCHEMAS;

        foreach ($schema as list('minAge' => $minAge, 'maxAge' => $maxAge, 'code' => $codeCover)) {
            if ($age <= $maxAge && $age >= $minAge) {
                $ageGroup = "$minAge-$maxAge";
                return $codeCover;
            }
        }

        return null;
    }
}
