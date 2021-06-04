<?php

namespace App\Http\Controllers;

use App\Filters\OwnerFilter;
use App\Http\Requests\CreateOwnerRequest;
use App\Models\Owners;
use Illuminate\Http\Request;

/**
 * Class OwnerController
 * @package App\Http\Controllers
 */
class OwnerController extends BaseController
{
    /**
     * OwnerController constructor.
     * @param Owners $model
     * @param OwnerFilter $filter
     */
    public function __construct(Owners $model, OwnerFilter $filter)
    {
        $this->model = $model;
        $this->filter = $filter;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/v1/owner",
     *     operationId="/v1/owner(GET)",
     *     summary="Список источников",
     *     tags={"Владельцы"},
     *     @OA\Parameter(
     *         name="filter-code",
     *         in="query",
     *         description="Выборка по коду",
     *         required=false,
     *         example="test",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="filter-name",
     *         in="query",
     *         description="Выборка по имени",
     *         required=false,
     *         example="testName",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="filter-uw_login",
     *         in="query",
     *         description="Выборка по UW логину",
     *         required=false,
     *         example="1234",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Возвращает список источников с возможностью фильтрации",
     *         @OA\JsonContent(example="")
     *     )
     * )
     *
     * Возвращает список источников с возможностью фильтрацию.
     * @param Request $request
     * @return array|\Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->initRequest($request);
        $this->setTotalCount($this->model::count());

        $this->model = $this->model::orderBy('id');
        $this->model->filter($this->filter);

        return parent::index($request);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param Request $request
     * @return \App\Models\BaseModel|\Illuminate\Database\Eloquent\Model
     */
    public function create(CreateOwnerRequest $request)
    {
        return $this->model::create($request->validated());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/v1/owner",
     *     operationId="/v1/owner(POST)",
     *     summary="Добавить новый источник",
     *     tags={"Владельцы"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/CreateOwnerRequest")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Создает новый источник",
     *         @OA\JsonContent(example="")
     *     )
     * )
     *
     * @param CreateOwnerRequest $request
     * @return Owners
     */
    public function store(CreateOwnerRequest $request)
    {
        $model = $this->model->fill($request->all());
        $model->save();

        return $model;
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/v1/owner/{id}",
     *     operationId="/v1/owner/{id}(PUT)",
     *     summary="Изменить источник",
     *     tags={"Владельцы"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Идентификатор источника",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/CreateOwnerRequest")
     *   ),
     *     @OA\Response(
     *         response="200",
     *         description="Обновляет существующий источник",
     *         @OA\JsonContent(example="")
     *     )
     * )
     * @param CreateOwnerRequest $request
     * @param int $id
     * @return \App\Models\BaseModel
     */
    public function update(CreateOwnerRequest $request, $id)
    {
        if (!($currentModel = $this->model::findOrFail($id))) {
            return null;
        }
        $attributes = $request->all();
        if (count($attributes) == 0) {
            $owner = $currentModel;
        } else {
            $owner = $currentModel->fill($attributes);
            $owner->save();
        }

        return $owner;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *     path="/v1/owner/{id}",
     *     operationId="/v1/owner/{id}(DELETE)",
     *     summary="Удалить источник",
     *     tags={"Владельцы"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Идентификатор источника",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Удаляет существующий источник",
     *         @OA\JsonContent(ref="#/components/schemas/Delete")
     *     )
     * )
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        return parent::destroy($id);
    }
}
