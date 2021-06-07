<?php

namespace App\Models;


use Strahovka\LaravelFilterable\Filterable;

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
 * @mixin \Eloquent
 */
class Owner extends BaseModel
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
        return $this->belongsToMany(Program::class, 'owners_programs');
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

    public function setUwLoginAttribute($value)
    {
        $this->attributes['uw_login'] = $value;
    }
}
