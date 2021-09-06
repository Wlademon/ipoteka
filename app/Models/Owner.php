<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
 * @property-read Contract $contracts
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

    /**
     * @return BelongsToMany
     */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class, 'owners_programs');
    }

    /**
     * @return string|null
     */
    public function getCodeAttribute(): ?string
    {
        return $this->attributes['code'];
    }

    /**
     * @return string|null
     */
    public function getNameAttribute(): ?string
    {
        return $this->attributes['name'];
    }

    /**
     * @return string|null
     */
    public function getUwLoginAttribute(): ?string
    {
        return $this->attributes['uw_login'];
    }

    /**
     * @param  string  $value
     */
    public function setUwLoginAttribute(string $value): void
    {
        $this->attributes['uw_login'] = $value;
    }
}
