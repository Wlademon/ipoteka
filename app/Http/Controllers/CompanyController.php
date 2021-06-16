<?php

namespace App\Http\Controllers;

use App\Filters\CompanyFilter;
use App\Http\Requests\CreateCompanyRequest;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class CompanyController
 * @package App\Http\Controllers
 */
class CompanyController extends BaseController
{
    /**
     * CompanyController constructor.
     * @param Company $model
     * @param CompanyFilter $filter
     */
    public function __construct(Company $model, CompanyFilter $filter)
    {
        $this->model = $model;
        $this->filter = $filter;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/v1/company",
     *     operationId="/v1/company(GET)",
     *     summary="Список компаний",
     *     tags={"Компании"},
     *     @OA\Parameter(
     *         name="filter-code",
     *         in="query",
     *         description="Выборка по коду",
     *         required=false,
     *         example="ALFA_MSK",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="filter-name",
     *         in="query",
     *         description="Выборка по имени",
     *         required=false,
     *         example="АО «АльфаСтрахование»",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="filter-is_active",
     *         in="query",
     *         description="Выборка по активности",
     *         required=false,
     *         example="1",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Возвращает список компаний с возможностью фильтрации",
     *         @OA\JsonContent(example="")
     *     )
     * )
     *
     * Возвращает список компаний с возможностью фильтрацию.
     * @param Request $request
     * @return JsonResource
     */
    public function index(Request $request): JsonResource
    {
        $this->initRequest($request);

        $this->model = Company::query()->orderBy('id');
        $this->model->filter($this->filter);

        return parent::index($request);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param CreateCompanyRequest $request
     * @return JsonResource
     */
    public function create(CreateCompanyRequest $request): JsonResource
    {
        return self::successResponse($this->model::create($request->validated()));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/v1/company",
     *     operationId="/v1/company(POST)",
     *     summary="Добавить новую компанию",
     *     tags={"Компании"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/CreateCompanyRequest")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Создает новую компанию",
     *         @OA\JsonContent(example="")
     *     )
     * )
     *
     * @param CreateCompanyRequest $request
     * @return JsonResource
     */
    public function store(CreateCompanyRequest $request): JsonResource
    {
        $company = $this->model->fill($request->all());
        $company->save();

        return self::successResponse($company);
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/v1/company/{id}",
     *     operationId="/v1/company/{id}(PUT)",
     *     summary="Изменить компанию",
     *     tags={"Компании"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Идентификатор компании",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/CreateCompanyRequest")
     *   ),
     *     @OA\Response(
     *         response="200",
     *         description="Обновляет существующую компанию",
     *         @OA\JsonContent(example="")
     *     )
     * )
     * @param CreateCompanyRequest $request
     * @param int $id
     * @return JsonResource
     */
    public function update(CreateCompanyRequest $request, int $id): JsonResource
    {
        $currentModel = $this->model->findOrFail($id);
        $attributes = $request->all();
        if (count($attributes) === 0) {
            $company = $currentModel;
        } else {
            $company = $currentModel->fill($attributes);
            $company->save();
        }

        return self::successResponse($company);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *     path="/v1/company/{id}",
     *     operationId="/v1/company/{id}(DELETE)",
     *     summary="Удалить компанию",
     *     tags={"Компании"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Идентификатор компании",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Удаляет существующую компанию",
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
