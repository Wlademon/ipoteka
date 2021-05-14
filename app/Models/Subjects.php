<?php

namespace App\Models;


/**
 * App\Models\Payment
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
class Subjects extends BaseModel
{
    protected $casts = [
        'value' => 'json',
    ];

    protected $fillable = [
        'value',
    ];

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
}
