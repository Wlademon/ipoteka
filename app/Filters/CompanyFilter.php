<?php

namespace App\Filters;

use Kyslik\LaravelFilterable\Generic\Filter;

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
    protected function settings()
    {
		//
	}
//    public function filterMap(): array
//    {
//        return ['deleted' => ['deleted'], 'code' => ['code']];
//    }
//
//	public function deleted($flag = null)
//    {
//        // TODO сделать три варианта
//        // Проверить работают ли женерики
//        return $this->builder->withTrashed()->whereNotNull('deleted_at');
//    }

}
