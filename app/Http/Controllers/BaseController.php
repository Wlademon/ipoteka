<?php

namespace App\Http\Controllers;

use App;
use App\Helpers\Helper;
use App\Http\Traits\ResponseTrait;
use App\Models\BaseModel;
use App\Repositories\Repository;
use Eloquent;
use Illuminate\Http\Response;
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
     * @param Request $request
     * @return array|Response
     */
    public function index(Request $request)
    {
        $model = $this->model;

        $items = $model->limit($this->limit)->offset($this->offset)->get()->all();

        return $this->getResponseForList($items);
    }

    /**
     * @param array $list
     * @param int $offset
     * @param false $prepare
     * @return array
     */
    protected function getResponseForList(array $list, $offset = 0, $prepare = false)
    {
        return [
            'success' => true,
            'count' => count($list),
            'totalCount' => (int)$this->totalCount,
            'offset' => $offset,
            'data' => $prepare ? $this->prepareItems($list) : $list,
        ];
    }

    /**
     * @param array $list
     * @return array
     */
    protected function prepareItems(array $list)
    {
        $result = [];
        /** @var BaseModel|array $list_item */
        foreach ($list as $list_item) {
            if ($list_item instanceof BaseModel) {
                $list_item = $list_item->toArray();
            }
            $list_item = $this->formatArrayItem($list_item);
            $result[] = $list_item;

        }

        return $result;
    }

    /**
     * Форматируем результат - делаем вложенные массивы по полям с
     * @param $item
     * @return array
     */
    protected function formatArrayItem($item)
    {
        $result = [];
        foreach ($item as $key => $value) {

            $keys = explode('.', $key);

            if (count($keys) > 1) {
                $json_value = json_decode($value, true);
                if (is_array($json_value)) {
                    $value = Helper::getLocaleAttr($json_value);
                }

                $data = $value;
                $reverseKey = array_reverse($keys);
                foreach ($reverseKey as $expKey) {
                    $data = [$expKey => $data];
                }
            } else {
                $data = [$key => $value];
            }
            $result = array_merge_recursive($result, $data);
        }

        return $result;
    }

    /**
     * @param $count
     * @return $this
     */
    protected function setTotalCount($count)
    {
        $this->totalCount = $count;

        return $this;
    }

    /**
     * @param Request $request
     */
    protected function initRequest(Request $request)
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
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|Response
     * @throws \Exception
     */
    public function destroy($id)
    {
        $model = $this->model::findOrFail($id);
        $model->delete();

        return $this->successResponse(["Запись {$id} успешно удалена"]);
    }
}
