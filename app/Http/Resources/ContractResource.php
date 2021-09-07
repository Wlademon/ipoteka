<?php

namespace App\Http\Resources;

use App\Models\Contract;
use App\Models\InsuranceObject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ContractResource
 * @package App\Http\Resources
 * @mixin Contract
 */
class ContractResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'contractId' => $this->ext_id,
            'companyCode' => $this->getCompanyCodeAttribute(),
            'programCode' => $this->getProgramCodeAttribute(),
            'activeFrom' => $this->active_from,
            'activeTo' => $this->active_to,
            'signetAt' => $this->signed_at ?? '',
            'remainingDebt' => $this->getAttribute('remainingDebt') ?? '',
            'mortgageAgreementNumber' => $this->getMortgageAgreementNumberAttribute(),
            'isOwnerShip' => $this->getIsOwnershipAttribute(),
            'mortgageeBank' => $this->getMortgageeBankAttribute(),
            'premium' => $this->premium,
            'status' => $this->status,
            'subject' => $this->subject->getValueAttribute(),
            'uwContractId' => $this->uw_contract_id,
            'objects' => InsuranceObject::contractObjects($this->id)
        ];
    }
}
