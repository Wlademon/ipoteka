<?php

namespace App\Models;


use App\Drivers\DriverResults\CreatedPolicyInterface;
use Illuminate\Support\Arr;

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
 * @mixin \Eloquent
 */
class Objects extends BaseModel
{
    const TYPE_PROPERTY = 'property';
    const TYPE_LIFE = 'life';

    const PROPERY_TYPE_FIAT = 'flat';

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

    public function toArray()
    {
        $data = collect($this->attributes);
        $data = $data->merge($this->getAttribute('value'));
        $data->forget('value');

        return $data->toArray();
    }

    public static function contractObjects($contractId)
    {
        return self::query()->where('contract_id', '=', $contractId)
                     ->get()
                     ->keyBy('product')
                     ->map(
                        function(Objects $object)
                        {
                            $val = $object->getValueAttribute();
                            $val = Arr::add($val, 'policyNumber', $object->number);
                            $val = Arr::add($val, 'premium', $object->premium);
                            return $val;
                        }
                    )->toArray();
    }

    public function loadFromDriverResult(CreatedPolicyInterface $createdPolicy)
    {
        if ($this->product === self::TYPE_PROPERTY) {
            $this->number = $createdPolicy->getPropertyPolicyNumber();
            $this->premium = $createdPolicy->getPropertyPremium();
            $this->external_id = $createdPolicy->getPropertyPolicyId();
        } else {
            $this->number = $createdPolicy->getLifePolicyNumber();
            $this->premium = $createdPolicy->getLifePremium();
            $this->external_id = $createdPolicy->getLifePolicyId();
        }
    }
}
