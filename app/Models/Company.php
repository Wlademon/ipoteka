<?php

namespace App\Models;


use Illuminate\Database\Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Strahovka\LaravelFilterable\Filterable;


/**
 * App\Models\Companies
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool $isActive
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Contract[] $contracts
 * @property-read int|null $policies_count
 * @mixin Eloquent
 */
class Company extends BaseModel
{
    use Filterable;

    const NAME = 'Список страховых компаний';

    protected $fillable = [
        'code',
        'name',
        'isActive',
        'inn',
    ];

    protected $visible = [
        'id',
        'code',
        'name',
        'isActive',
        'inn',
    ];

    protected $appends = [
        'code',
        'name',
        'isActive',
    ];

    /**
     * @return HasMany
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * @return HasMany
     */
    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    /**
     * @return mixed
     */
    public function getCodeAttribute(): string
    {
        return $this->attributes['code'];
    }

    /**
     * @return mixed
     */
    public function getNameAttribute(): string
    {
        return $this->attributes['name'];
    }

    /**
     * @return mixed
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->attributes['is_active'];
    }

    /**
     * @param $val
     *
     * @return bool
     */
    public function setIsActiveAttribute(bool $val): bool
    {
        return $this->attributes['is_active'] = $val;
    }
}
