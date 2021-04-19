<?php

namespace App\Filters;

use Kyslik\LaravelFilterable\Generic\Filter;

class PaymentFilter extends Filter
{
    /**
     * Defines columns that end-user may filter by.
     *
     * @var array
     */
    protected $filterables = ['id', 'contract_id', 'order_id', 'invoice_num'];


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
