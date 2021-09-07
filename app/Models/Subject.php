<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Payment
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Contract $contracts
 * @mixin \Eloquent
 */
class Subject extends BaseModel
{
    protected $casts = [
        'value' => 'json',
    ];

    protected $fillable = [
        'value',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    /**
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
     * @throws \JsonException
     */
    public function getValueAttribute(): array
    {
        return json_decode($this->attributes['value'], true, 512, JSON_THROW_ON_ERROR);
    }

    public function toArray(): array
    {
        $data = collect($this->attributes);
        $data = $data->merge($this->getAttribute('value'));
        $data->forget('value');

        return $data->toArray();
    }
}
