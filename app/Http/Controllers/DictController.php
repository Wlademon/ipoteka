<?php


namespace App\Http\Controllers;

use OpenApi\Annotations as OA;
use App\Models\Programs;
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
     *     summary="Спровочник программ",
     *     tags={"Справочники"},
     *     @OA\Response(
     *         response="200",
     *         description="Спровочник программ",
     *         @OA\JsonContent(
     *              ref="#/components/schemas/Directoryes"
     *         )
     *     )
     * )
     *
     * Справочник программ.
     *
     */
    public function getDictPrograms()
    {
        $result = Programs::active()->get();

        return $this->successResponse($result);
    }
}
