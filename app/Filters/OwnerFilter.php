<?php

namespace App\Filters;

use Strahovka\LaravelFilterable\Generic\Filter;

/**
 * Class OwnerFilter
 *
 * @package App\Filters
 */
class OwnerFilter extends Filter
{
    /**
     * Defines columns that end-user may filter by.
     *
     * @var array
     */
    protected $filterables = ['id', 'code', 'name', 'uw_login'];


    /**
     * Define allowed generics, and for which fields.
     * Read more in the documentation https://github.com/Kyslik/laravel-filterable#additional-configuration
     *
     * @return void
     */
    protected function settings(): void
    {
		//
	}
}
