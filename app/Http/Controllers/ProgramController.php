<?php

namespace App\Http\Controllers;

use App\Filters\ProgramFilter;
use App\Http\Requests\CreateProgramRequest;
use App\Models\Program;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgramController extends BaseController
{
    public function __construct(ProgramFilter $filter)
    {
        $this->model = new Program();
        $this->filter = $filter;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/v1/program",
     *     operationId="/v1/program(GET)",
     *     summary="Список программ",
     *     tags={"Программы"},
     *     @OA\Parameter(
     *         name="filter-program_code",
     *         in="query",
     *         description="Выборка по коду программы",
     *         required=false,
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
     *     @OA\Parameter(
     *         name="filter-insured_sum",
     *         in="query",
     *         description="Выборка по сумме выплаты",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Лимит записей на вывод",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         description="Шаг записей на вывод",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Возвращает список программ с возможностью фильтрации",
     *         @OA\JsonContent(example="")
     *     )
     * )
     *
     * Возвращает список программ с возможностью фильтрации.
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        /** @var Builder $query */
        $query = Program::query();
        $this->initRequest($request);
        $this->setTotalCount(Program::count());
        if ($insuredSum = $request->get('insuredSum')) {
            $query->where('insured_sum', '>', $insuredSum);
        }
        if ($companyIsActive = $request->get('companyIsActive')) {
            $query->whereHas('company', function($q) use ($companyIsActive) {
                $q->where('is_active', filter_var($companyIsActive, FILTER_VALIDATE_BOOLEAN));
            });
        }
        $query->with(['company'])->orderBy('id');
        $this->model = $query;
        $this->model->filter($this->filter);

        return parent::index($request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/v1/program",
     *     operationId="/v1/program(POST)",
     *     summary="Добавить новую программу",
     *     tags={"Программы"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/CreateProgramRequest")
     *   ),
     *     @OA\Response(
     *         response="200",
     *         description="Создает новую программу",
     *         @OA\JsonContent(example="")
     *     )
     * )
     *
     * @param CreateProgramRequest $request
     * @return JsonResponse
     */
    public function store(CreateProgramRequest $request): JsonResponse
    {
        $program = (new Program())->fill($request->all());
        $program->save();

        return response()->json(
            [
                'success' => true,
                'data' => $program,
            ]
        );
    }


    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/v1/program/{id}",
     *     operationId="/v1/program/{id}(PUT)",
     *     summary="Изменить программу",
     *     tags={"Программы"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Идентификатор программы",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/CreateProgramRequest")
     *   ),
     *     @OA\Response(
     *         response="200",
     *         description="Обновляет существующую программу",
     *         @OA\JsonContent(example="")
     *     )
     * )
     * @param CreateProgramRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(CreateProgramRequest $request, int $id): JsonResponse
    {
        $currentModel = Program::findOrFail($id);
        $attributes = $request->all();
        if (count($attributes) === 0) {
            $program = $currentModel;
        } else {
            $program = $currentModel->fill($attributes);
            $program->save();
        }

        return response()->json(
            [
                'success' => true,
                'data' => $program,
            ]
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *     path="/v1/program/{id}",
     *     operationId="/v1/program/{id}(DELETE)",
     *     summary="Удалить программу",
     *     tags={"Программы"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Идентификатор программы",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Удаляет существующую программу",
     *         @OA\JsonContent(ref="#/components/schemas/Delete")
     *     )
     * )
     * @param int $id
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function destroy(int $id): JsonResponse
    {
        return parent::destroy($id);
    }
}
