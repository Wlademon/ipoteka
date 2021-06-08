<?php

namespace App\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;

class JsonRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param mixed  $data
     * @return array
     */
    public function toArray($data)
    {
        return [
            'success' => true,
            'data' => $this->resource
        ];
    }
}
