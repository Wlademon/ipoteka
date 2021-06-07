<?php

namespace App\Models;


use Illuminate\Database\Eloquent;
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
 * @property-read \App\Models\Contracts[] $contracts
 * @property-read int|null $policies_count
 * @mixin Eloquent
 */
class Companie extends BaseModel
{
    use Filterable;

    const NAME = 'Список страховых компаний';

    protected $fillable = [
        'code',
        'name',
        'isActive',
    ];

    protected $visible = [
        'id',
        'code',
        'name',
        'isActive',
    ];

    protected $appends = [
        'code',
        'name',
        'isActive',
    ];

    public function contracts()
    {
        return $this->hasMany(Contracts::class);
    }

    public function getCodeAttribute()
    {
        return $this->attributes['code'];
    }

    public function getNameAttribute()
    {
        return $this->attributes['name'];
    }

    public function getIsActiveAttribute()
    {
        return $this->attributes['is_active'];
    }

    public function setIsActiveAttribute($val)
    {
        return $this->attributes['is_active'] = $val;
    }
}
