<?php

namespace App\Models;


use Illuminate\Database\Eloquent;
use Kyslik\LaravelFilterable\Filterable;


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
 * @method static Eloquent\Builder|\App\Models\Companies newModelQuery()
 * @method static Eloquent\Builder|\App\Models\Companies newQuery()
 * @method static Eloquent\Builder|\App\Models\Companies query()
 * @method static Eloquent\Builder|\App\Models\Companies whereCode($value)
 * @method static Eloquent\Builder|\App\Models\Programs whereIsActive($value)
 * @method static Eloquent\Builder|\App\Models\Companies whereCreatedAt($value)
 * @method static Eloquent\Builder|\App\Models\Companies whereDeletedAt($value)
 * @method static Eloquent\Builder|\App\Models\Companies whereId($value)
 * @method static Eloquent\Builder|\App\Models\Companies whereName($value)
 * @method static Eloquent\Builder|\App\Models\Companies whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Companies extends BaseModel
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
