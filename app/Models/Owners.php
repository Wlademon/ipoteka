<?php

namespace App\Models;


use Kyslik\LaravelFilterable\Filterable;

/**
 * App\Models\Payment
 *
 * @property int $id
 * @property string $code код канала, откуда идут запросы для методов
 * @property string $name имя канала, откуда идут запросы для методов
 * @property string $uwLogin логин в UW
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
class Owners extends BaseModel
{
    use Filterable;

    protected $fillable = [
        'code',
        'name',
        'uwLogin',
    ];

    protected $visible = [
        'id',
        'code',
        'name',
        'uwLogin',
    ];

    protected $appends = [
        'code',
        'name',
        'uwLogin',
    ];

    public function programs()
    {
        return $this->belongsToMany(Programs::class, 'owners_programs');
    }

    public function getCodeAttribute()
    {
        return $this->attributes['code'];
    }

    public function getNameAttribute()
    {
        return $this->attributes['name'];
    }

    public function getUwLoginAttribute()
    {
        return $this->attributes['uw_login'];
    }
}
