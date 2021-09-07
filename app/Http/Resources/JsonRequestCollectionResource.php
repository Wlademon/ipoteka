<?php

namespace App\Http\Resources;

use Illuminate\Database\Eloquent\Builder as BuilderEq;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class JsonRequestCollectionResource
 *
 * @package App\Http\Resources
 */
class JsonRequestCollectionResource extends JsonResource
{
    const MAX_LIMIT = 1000;
    /** @var Model|Builder */
    public $resource;
    protected int $limit = 10;
    protected int $offset = 0;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $limit = $request->get('limit', $this->limit);
        if ($limit > self::MAX_LIMIT) {
            $limit = self::MAX_LIMIT;
        }
        if ($this->resource instanceof Model) {
            $query = $this->resource->newQuery();
        } elseif (
            $this->resource instanceof Builder ||
            $this->resource instanceof BuilderEq
        ) {
            $query = $this->resource;
        }

        $total = $query->count();
        $query->limit($limit);
        $query->offset($request->get('offset', $this->offset));

        $items = $query->get();

        return [
            'success' => true,
            'count' => $items->count(),
            'totalCount' => $total,
            'offset' => $this->offset,
            'data' => $items,
        ];
    }
}
