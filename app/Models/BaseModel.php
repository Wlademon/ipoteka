<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Class BaseModel
 *
 * @property integer $id
 * @method static BaseModel find($id)
 * @method static BaseModel get()
 * @method static BaseModel whereNotActive($value)
 * @method static BaseModel whereOwnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BaseModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BaseModel newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BaseModel query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel withoutTrashed()
 * @mixin Model
 */
class BaseModel extends Model
{
    use SoftDeletes;

    const NAME = 'основная';

    public static $snakeAttributes = true;

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * Fill the model with an array of attributes.
     *
     * @param  array  $attributes
     * @return $this
     *
     * @throws \Illuminate\Database\Eloquent\MassAssignmentException
     */
    public function fill(array $attributes)
    {
        $totallyGuarded = $this->totallyGuarded();

        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            } elseif ($this->isFillable(Str::snake($key))) {
                $this->setAttribute(Str::snake($key), $value);
            } elseif ($totallyGuarded) {
                $class = get_class($this);
                throw new MassAssignmentException(
                    "Add [{$key}] to fillable property to allow mass assignment on [{$class}].",
                );
            }
        }

        return $this;
    }

    /**
     * Save the model to the database.
     *
     * @param  array  $options
     * @return bool
     */
    public function save(array $options = [])
    {
        return parent::save($options);
    }

    /**
     * @return string
     */
    public static function getTableName()
    {
        /** @var BaseModel $model */
        $model = with(new static);
        return $model->getTable();
    }

    /**
     * @param  array  $field
     *
     * @return mixed|null
     */
    public function getLocaleAttr(array $field)
    {
        return Helper::getLocaleAttr($field);
    }

    // Allow for camelCased attribute access

    /**
     * Get an attribute from the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (array_key_exists($key, $this->relations) || method_exists($this, $key)) {
            return parent::getAttribute($key);
        }

        return parent::getAttribute(Str::snake($key));
    }

    /**
     * @return mixed
     */
    public function getCreatedAtAttribute()
    {
        return $this->attributes['created_at'];
    }

    /**
     * @return mixed
     */
    public function getUpdatedAtAttribute()
    {
        return $this->attributes['updated_at'];
    }

    /**
     * @return mixed
     */
    public function getDeletedAtAttribute()
    {
        return $this->attributes['deleted_at'];
    }
}
