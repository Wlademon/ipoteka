<?php

namespace App\Http\Controllers;

use App\Filters\ContractFilter;
use App\Http\Requests\CreatePolicyRequest;
use App\Models\Contract;
use App\Services\DriverService;
use App\Services\PolicyService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ContractController
 * @package App\Http\Controllers
 */
class ContractController extends BaseController
{
    public function __construct(Contract $model, ContractFilter $filter)
    {
        $this->model = $model;
        $this->filter = $filter;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/v1/contract",
     *     operationId="/v1/contract(GET)",
     *     summary="Список договоров",
     *     tags={"Сделки"},
     *     @OA\Parameter(
     *         name="filter-premium",
     *         in="query",
     *         description="Выборка по премии",
     *         required=false,
     *         example="><1000,9000",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="filter-status",
     *         in="query",
     *         description="Выборка по статусу",
     *         required=false,
     *         example="1",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="filter-program_id",
     *         in="query",
     *         description="Выборка по продукту",
     *         required=false,
     *         example="1",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="filter-company_id",
     *         in="query",
     *         description="Выборка по компании",
     *         required=false,
     *         example="1",
     *         @OA\Schema(type="integer")
     *     ),

     *     @OA\Parameter(
     *         name="filter-signed_at",
     *         in="query",
     *         description="Выборка по дате подписания",
     *         required=false,
     *         example="t>=1510952444",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="filter-active_from",
     *         in="query",
     *         description="Выборка по дате начала действия",
     *         required=false,
     *         example="t>=1510952444",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="filter-active_to",
     *         in="query",
     *         description="Выборка по дате окончания действия",
     *         required=false,
     *         example="t>=1510952444",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Возвращает список договоров с возможностью фильтрации",
     *         @OA\JsonContent(example="")
     *     )
     * )
     *
     * Возвращает список договоров с возможностью фильтрацию.
     * @param Request $request
     * @return JsonResource
     */
    public function index(Request $request): JsonResource
    {
        $this->initRequest($request);

        $this->model = $this->model->orderBy('id');
        $this->model->filter($this->filter);

        return parent::index($request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/v1/contract",
     *     operationId="/v1/contract(POST)",
     *     summary="Добавить новый договор",
     *     tags={"Сделки"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/CreatePolicyRequest")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Создает новый договор",
     *         @OA\JsonContent(example="")
     *     )
     * )
     *
     * @param CreatePolicyRequest $request
     * @param PolicyService $service
     * @param DriverService $driver
     * @return JsonResource
     * @throws \Throwable
     */
    public function store(
        CreatePolicyRequest $request,
        PolicyService $service,
        DriverService $driver
    ): JsonResource {
        $result = $service->savePolicy($request, $driver);

        return self::successResponse($this->model::findOrFail($result['contractId']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/v1/contract/{id}",
     *     operationId="/v1/contract/{id}(PUT)",
     *     summary="Изменить договор",
     *     tags={"Сделки"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Идентификатор договора",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/CreatePolicyRequest")
     *   ),
     *     @OA\Response(
     *         response="200",
     *         description="Обновляет существующий договор",
     *         @OA\JsonContent(example="")
     *     )
     * )
     * @param CreatePolicyRequest $request
     * @param PolicyService $service
     * @param DriverService $driver
     * @param int $id
     * @return JsonResource
     * @throws \Exception
     */
    public function update(
        CreatePolicyRequest $request,
        PolicyService $service,
        DriverService $driver,
        int $id
    ): JsonResource {
        $currentModel = $this->model::findOrFail($id);
        $attributes = $request->all();
        if (count($attributes) === 0) {
            $contract = $currentModel;
        } else {
            $request['id'] = $id;
            $result = $service->savePolicy($request, $driver);
            $contract = $this->model::findOrFail($result['contractId']);
        }

        return self::successResponse($contract);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *     path="/v1/contract/{id}",
     *     operationId="/v1/contract/{id}(DELETE)",
     *     summary="Удалить договор",
     *     tags={"Сделки"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Идентификатор договора",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Удаляет существующий договор",
     *         @OA\JsonContent(ref="#/components/schemas/Delete")
     *     )
     * )
     * @param int $id
     * @return JsonResource
     * @throws \Throwable
     */
    public function destroy(int $id): JsonResource
    {
        return parent::destroy($id);
    }
}
