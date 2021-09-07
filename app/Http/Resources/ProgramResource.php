<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ProgramResource
 *
 * @package App\Http\Resources
 */
class ProgramResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'companyCode' => $this->company_code,
            'companyName' => $this->company_name,
            'programCode' => $this->program_code,
            'programName' => $this->program_name,
            'programUwCode' => $this->program_uw_code,
            'description' => $this->description,
            'risks' => $this->risks,
            'issues' => $this->issues,
            'conditions' => $this->conditions,
            'isProperty' => $this->is_property,
            'isLife' => $this->is_life,
            'isTitle' => $this->is_title,
            'isRecommended' => $this->is_recommended,
            'isActive' => $this->is_active,
        ];
    }
}
