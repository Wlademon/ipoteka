<?php

namespace App\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class JsonRequestResource
 *
 * @package App\Http\Resources
 */
class JsonRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param mixed  $data
     * @return array
     */
    public function toArray($data): array
    {
        return [
            'success' => true,
            'data' => $this->resource
        ];
    }
}
