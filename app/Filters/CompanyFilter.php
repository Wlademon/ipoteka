<?php

namespace App\Filters;

use Strahovka\LaravelFilterable\Generic\Filter;

/**
 * Class CompanyFilter
 *
 * @package App\Filters
 */
class CompanyFilter extends Filter
{
    /**
     * Defines columns that end-user may filter by.
     *
     * @var array
     */
    protected $filterables = ['id', 'code', 'name', 'is_active'];


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
