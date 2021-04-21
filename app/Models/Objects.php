<?php

namespace App\Models;


/**
 * App\Models\Payment
 *
 *
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Contracts $contracts
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payments newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payments newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payments query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payments whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payments whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payments whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payments whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payments whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payments whereUwLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payments whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Objects extends BaseModel
{
    const TYPE_PROPERTY = 'property';
    const TYPE_LIFE = 'life';

    const PROPERY_TYPE_FIAT = 'fiat';

    protected $fillable = [
        'contract_id',
        'value',
        'product',
        'number',
        'premium',
        'external_id',
        'uw_contract_id',
    ];

    protected $casts = [
        'value' => 'array'
    ];

    public static function propertyTypes($isImplode = false)
    {
        $types = [self::PROPERY_TYPE_FIAT];
        if ($isImplode) {
            return implode(',', $types);
        }

        return $types;
    }

    public static function types($isImplode = false)
    {
        $types = [self::TYPE_LIFE, self::TYPE_PROPERTY];
        if ($isImplode) {
            return implode(',', $types);
        }

        return $types;
    }

    public function contract()
    {
        return $this->belongsTo(Contracts::class, 'contract_id');
    }

    public function setValueAttributes($value)
    {
        $this->attributes['value'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function getValueAttribute()
    {
        return json_decode($this->attributes['value'], true);
    }
}
