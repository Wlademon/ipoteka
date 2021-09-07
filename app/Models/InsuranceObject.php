<?php

namespace App\Models;

use App\Drivers\DriverResults\CreatedPolicyInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;

/**
 * App\Models\Payment
 *
 * @property int                             $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Contract                   $contracts
 * @mixin \Eloquent
 */
class InsuranceObject extends BaseModel
{
    protected $table = 'objects';
    public const TYPE_PROPERTY = 'property';
    public const TYPE_LIFE = 'life';
    public const PROPERTY_TYPE_FIAT = 'flat';
    protected $fillable = [
        'contract_id',
        'value',
        'product',
        'number',
        'premium',
        'integration_id',
        'uw_contract_id',
    ];
    protected $casts = [
        'value' => 'array',
    ];

    /**
     * @param  false  $isImplode
     *
     * @return string|string[]
     */
    public static function propertyTypes(bool $isImplode = false)
    {
        $types = [self::PROPERTY_TYPE_FIAT];
        if ($isImplode) {
            return implode(',', $types);
        }

        return $types;
    }

    /**
     * @param  false  $isImplode
     *
     * @return string|string[]
     */
    public static function types(bool $isImplode = false)
    {
        $types = [self::TYPE_LIFE, self::TYPE_PROPERTY];
        if ($isImplode) {
            return implode(',', $types);
        }

        return $types;
    }

    /**
     * @return BelongsTo
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    /**
     * @param  array  $value
     *
     * @throws \JsonException
     */
    public function setValueAttribute(array $value): void
    {
        $this->attributes['value'] = json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * @return array
     * @throws \JsonException
     */
    public function getValueAttribute(): array
    {
        return json_decode($this->attributes['value'], true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = collect($this->attributes);
        $data = $data->merge($this->getAttribute('value'));
        $data->forget('value');

        return $data->toArray();
    }

    /**
     * @param  string  $contractId
     *
     * @return array
     */
    public static function contractObjects(string $contractId): array
    {
        return self::query()->where('contract_id', '=', $contractId)->get()->keyBy('product')->map(
            function (InsuranceObject $object)
            {
                $val = $object->getValueAttribute();
                $val = Arr::add($val, 'policyNumber', $object->number);

                return Arr::add($val, 'premium', $object->premium);
            }
        )->toArray();
    }

    /**
     * @param  CreatedPolicyInterface  $createdPolicy
     */
    public function loadFromDriverResult(CreatedPolicyInterface $createdPolicy): void
    {
        if ($this->product === self::TYPE_PROPERTY) {
            $this->number = $createdPolicy->getPropertyPolicyNumber();
            $this->premium = $createdPolicy->getPropertyPremium();
            $this->integration_id = $createdPolicy->getPropertyPolicyId();
        } else {
            $this->number = $createdPolicy->getLifePolicyNumber();
            $this->premium = $createdPolicy->getLifePremium();
            $this->integration_id = $createdPolicy->getLifePolicyId();
        }
    }
}
