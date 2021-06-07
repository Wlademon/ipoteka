<?php

namespace App\Http\Controllers;

use App;
use App\Http\Resources\JsonRequestCollectionResource;
use App\Http\Resources\JsonRequestResource;
use App\Models\BaseModel;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

/**
 * Class BaseController
 * @package App\Http\Controllers
 */
abstract class BaseController extends Controller
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    /** @var  BaseModel|Eloquent|Builder $model */
    protected $model;
    protected $search = '';
    protected $locale;
    protected $filter;

    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @return JsonResource
     */
    public function index(Request $request): JsonResource
    {
        return self::successCollectResponse($this->model);
    }

    /**
     * @param  Request  $request
     */
    protected function initRequest(Request $request): void
    {
        $this->search = $request->input('search', '');
        $this->locale = App::getLocale() ?: 'ru';
    }

    /**
     * @param int $id
     * @return JsonResource
     * @throws \Exception
     */
    public function destroy(int $id): JsonResource
    {
        $model = $this->model::findOrFail($id);
        $model->delete();

        return self::successResponse(["Запись {$id} успешно удалена"]);
    }

    public static function successResponse($data): JsonResource
    {
        return self::response(new JsonRequestResource($data));
    }

    protected static function response(JsonResource $data): JsonResource
    {
        return $data;
    }

    /**
     * @param Model|Builder $data
     * @return JsonResource
     */
    public static function successCollectResponse($data): JsonResource
    {
        return self::response(new JsonRequestCollectionResource($data));
    }
}
