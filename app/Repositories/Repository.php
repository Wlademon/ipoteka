<?php

namespace App\Repositories;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;

abstract class Repository extends BaseRepository
{
    /** @var BaseModel $model */
    protected BaseModel $model;

    public function __construct(BaseModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param BaseModel $model
     */
    public function setModel(BaseModel $model): void
    {
        $this->model = $model;
    }

    /**
     * @param  array  $attributes
     *
     * @return BaseModel|Model
     */
    public function create(array $attributes = [])
    {
        return $this->model->create($attributes);
    }

    /**
     * @param  array  $options
     *
     * @return bool
     */
    public function save(array $options = [])
    {
        return $this->model->save($options);
    }

    /**
     * @param array $attributes
     * @return BaseModel
     */
    public function update(array $attributes = []): BaseModel
    {
        $this->model->update($attributes);

        return $this->model;
    }


}
