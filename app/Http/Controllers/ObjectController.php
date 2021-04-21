<?php

namespace App\Http\Controllers;

use App\Http\Requests\ObjectRequest;
use App\Models\Objects;
use Illuminate\Http\Request;

/**
 * Class ObjectController
 *
 * @package App\Http\Controllers
 */
class ObjectController extends BaseController
{
    public function __construct(Objects $model)
    {
        $this->model = $model;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/v1/object",
     *     operationId="/v1/object(POST)",
     *     summary="Добавить новый объект договора",
     *     tags={"Объекты"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/ObjectRequest")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Создает новый объект сделки",
     *         @OA\JsonContent(example="")
     *     )
     * )
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Models\BaseModel
     */
    public function store(ObjectRequest $request)
    {
        $model = $this->model->fill($request->validated());
        $model->saveOrFail();

        return $model;
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *     path="/v1/object",
     *     operationId="/v1/object(GET)",
     *     summary="Список объектов",
     *     tags={"Объекты"},
     *     @OA\Response(
     *         response="200",
     *         description="Возвращает список объектов",
     *         @OA\JsonContent(example="")
     *     )
     * )
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return parent::index($request);
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Post(
     *     path="/v1/object/{id}",
     *     operationId="/v1/object/{id}(POST)",
     *     summary="Изменить объект договора",
     *     tags={"Объекты"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Идентификатор компании",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/ObjectRequest")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Объект сделки",
     *         @OA\JsonContent(example="")
     *     )
     * )
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \App\Models\BaseModel
     */
    public function update(ObjectRequest $request, $id)
    {
        $model = $this->model::query()->where(['id' => $id])->firstOrFail();
        $model->fill($request->validated())->saveOrFail();

        return $model;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *     path="/v1/object/{id}",
     *     operationId="/v1/object/{id}(DELETE)",
     *     summary="Удалить объект",
     *     tags={"Объекты"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Идентификатор объекта",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Удаляет объект страхования",
     *         @OA\JsonContent(ref="#/components/schemas/Delete")
     *     )
     * )
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        return parent::destroy($id);
    }
}
