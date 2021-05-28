<?php

namespace App\Repositories;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;

abstract class Repository extends BaseRepository
{
    /** @var BaseModel $model */
    protected $model;

    public function __construct(BaseModel $model)
    {
        $this->model = $model;
    }

    public function setModel($model)
    {
        $this->model = $model;
    }

    /**
     * @param array $attributes
     * @param bool|true $addOwner
     * @return BaseModel|Model
     */
    public function create(array $attributes = [])
    {
        return $this->model->create($attributes);
    }

    /**
     * @param array $options
     * @param bool|false $logging
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
    public function update(array $attributes = [])
    {
        $this->model->update($attributes);

        return $this->model;
    }


}
