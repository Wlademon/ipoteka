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

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function programs()
    {
        return $this->hasMany(Program::class);
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
