<?php

namespace App\Filters;

use Kyslik\LaravelFilterable\Generic\Filter;

class ContractFilter extends Filter
{
    /**
     * Defines columns that end-user may filter by.
     *
     * @var array
     */
    protected $filterables = ['id', 'number', 'status', 'premium', 'active_from', 'active_to','owner_id', 'signed_at', 'program_id', 'company_id', 'uw_contract_id'];


    /**
     * Define allowed generics, and for which fields.
     * Read more in the documentation https://github.com/Kyslik/laravel-filterable#additional-configuration
     *
     * @return void
     */
    protected function settings()
    {
		//
	}
}
