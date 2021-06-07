<?php

namespace App\Filters;

use Strahovka\LaravelFilterable\Filter;

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

	public function created($flag = null)
    {
        return $this->builder->withTrashed()->whereNotNull('created_at');
    }

    public function updated($flag = null)
    {
        return $this->builder->withTrashed()->whereNotNull('updated_at');
    }

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
