<?php

namespace App\Models;


use Carbon\Carbon;

/**
 * App\Models\Tokens
 *
 * @property int $id
 * @property string $token
 * @property string $driver
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @mixin \Eloquent
 */
class Tokens extends BaseModel
{
    protected $fillable = [
        'token',
        'driver',
        'expires_at',
    ];

    /**
     * @param string $service
     * @return string|null
     */
    public static function getValue(string $code): ?string
    {
        /** @var Tokens $model */
        $model = self::query()->where(['driver' => $code])->firstOrNew(['driver' => $code]);
        if ($model->expires_at) {
            if (Carbon::parse($model->expires_at) < Carbon::now()) {
                return null;
            }
        }
        return $model->token;
    }

    /**
     * @param  string  $code
     * @param  string  $value
     * @param  \DateTimeInterface|null  $expires_at
     * @return bool
     */
    public static function setValue(
        string $code,
        string $value,
        ?\DateTimeInterface $expires_at = null
    ): bool {
        return (bool)Tokens::updateOrInsert(
            ['driver' => $code],
            [
                'token' => $value,
                'expires_at' => $expires_at ? $expires_at->format('Y-m-d H:i:s') : null
            ]
        );
    }
}
