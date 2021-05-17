<?php

namespace App\Http\Resources;

use App\Models\Contracts;
use App\Models\Objects;

/**
 * Class ContractResource
 * @package App\Http\Resources
 * @mixin Contracts
 */
class ContractResource extends \Illuminate\Http\Resources\Json\JsonResource
{

    public function toArray($request)
    {
        return [
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
            'objects' => Objects::contractObjects($this->id)
        ];
    }
}
