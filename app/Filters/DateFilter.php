<?php

namespace App\Filters;

use Strahovka\LaravelFilterable\Filter;

/**
 * Class DateFilter
 *
 * @package App\Filters
 */
class DateFilter extends Filter
{
    public function filterMap(): array
    {
        return [
            'created' => ['created'],
            'updated' => ['updated'],
            'deleted' => ['deleted'],
        ];
    }

    /**
     *
     * @return mixed
     */
    public function created()
    {
        return $this->builder->withTrashed()->whereNotNull('created_at');
    }

    /**
     * @return mixed
     */
    public function updated()
    {
        return $this->builder->withTrashed()->whereNotNull('updated_at');
    }

    /**
     * @param  null  $flag
     *
     * @return mixed
     */
    public function deleted($flag = null)
    {
        if ($flag === true) {
            return $this->builder->withTrashed()->whereNull('deleted_at');
        }
        if ($flag === false) {
            return $this->builder->withTrashed()->whereNotNull('deleted_at');
        }

        return $this->builder->withTrashed();
    }

}
