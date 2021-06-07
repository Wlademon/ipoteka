<?php

namespace App\Http\Controllers;

use App;
use App\Http\Traits\ResponseTrait;
use App\Models\BaseModel;
use App\Repositories\Repository;
use Eloquent;
use Illuminate\Http\JsonResponse;
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
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, ResponseTrait;

    /** @var  Repository $repository */
    protected $repository;
    /** @var  BaseModel|Eloquent $model */
    protected $model;
    protected $limit = 10;
    protected $offset = 0;
    protected $search = '';
    protected $totalCount = 0;
    protected $maxLimit = 1000;
    protected $locale;
    protected $filter;

    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $model = $this->model;

        $items = $model->limit($this->limit)->offset($this->offset)->get()->all();

        return response()->json(
            [
                'success' => true,
                'count' => count($items),
                'totalCount' => $this->totalCount,
                'offset' => $this->offset,
                'data' => $items,
            ]
        );
    }

    /**
     * @param $count
     * @return $this
     */
    protected function setTotalCount($count): BaseController
    {
        $this->totalCount = $count;

        return $this;
    }

    /**
     * @param  Request  $request
     */
    protected function initRequest(Request $request): void
    {
        $this->limit = (int)$request->input('limit', 100);
        if ($this->limit > $this->maxLimit) {
            $this->limit = $this->maxLimit;
        }
        $this->offset = (int)$request->input('offset', 0);
        $this->search = $request->input('search', '');
        $this->locale = App::getLocale() ?: 'ru';
    }

    /**
     * @param $id
     * @return JsonResponse
     * @throws \Exception
     */
    public function destroy(int $id): JsonResponse
    {
        $model = $this->model::findOrFail($id);
        $model->delete();

        return response()->json(
            [
                'success' => true,
                'data' => ["Запись {$id} успешно удалена"],
            ]
        );
    }
}
