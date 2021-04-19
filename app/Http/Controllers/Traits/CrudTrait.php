<?php

namespace App\Http\Controllers\Traits;

use App\Http\Responses\Interfaces\ResponseInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait CrudTrait
{
    protected $model;

    protected $query;

    protected $limit = 100;

    protected $offset = 0;

    public function index(Request $request): ResponseInterface
    {
        $result = $this->getQuery()->limit($this->limit)->offset($this->offset)->get();
        $response = $this->getResponse()->setData($result, ResponseInterface::READ, true);
        $response->setMetadata([
            'count' => $result->count(),
            'totalCount' => $this->resetQuery()->count(),
            'offset' => $this->offset
        ]);
        return $response;
    }

    public function show(int $id): ResponseInterface
    {

    }

    public function save(Request $request): ResponseInterface
    {

    }

    public function update(Request $request, int $id): ResponseInterface
    {

    }

    public function destroy(int $id): ResponseInterface
    {

    }

    protected function getQuery(): Builder
    {
        if (!$this->query) {
            $this->resetQuery();
        }

        return $this->query;
    }

    protected function resetQuery(): Builder
    {
        return $this->query = $this->model::query();
    }

    protected abstract function getResponse(): ResponseInterface;
}
