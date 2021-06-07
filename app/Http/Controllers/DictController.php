<?php


namespace App\Http\Controllers;

use App\Http\Resources\ProgramResource;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;
use App\Models\Program;
use Illuminate\Support\Facades\DB;

/**
 * Class DictController
 * @package App\Http\Controllers
 */
class DictController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/v1/dict/programs",
     *     operationId="/v1/dict/programs",
     *     summary="Справочник программ",
     *     tags={"Справочники"},
     *     @OA\Response(
     *         response="200",
     *         description="Справочник программ",
     *         @OA\JsonContent(
     *              ref="#/components/schemas/Directoryes"
     *         )
     *     )
     * )
     *
     * Справочник программ.
     *
     */
    public function getDictPrograms(): JsonResponse
    {
        $result = ProgramResource::collection(Program::active()->get());

        return response()->json(
            [
                'success' => true,
                'data' => $result
            ]
        );
    }
}
