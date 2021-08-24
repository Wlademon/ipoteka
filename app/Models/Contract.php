<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Strahovka\LaravelFilterable\Filterable;

/**
 * App\Models\Contracts
 *
 * @property int $id
 * @property int|null $programId
 * @property string $number
 * @property int $type
 * @property int $ownerId
 * @property array|null $options
 * @property int $status
 * @property string $statusText
 * @property int $insured_sum
 * @property float $premium
 * @property int $integration_id
 * @property array|null $calcCoeff
 * @property \Illuminate\Support\Carbon|null $active_from
 * @property \Illuminate\Support\Carbon|null $active_to
 * @property \Illuminate\Support\Carbon|null $signed_at
 * @property \Illuminate\Support\Carbon|null $createdAt
 * @property \Illuminate\Support\Carbon|null $updatedAt
 * @property-read string $subjectFullname
 * @property-read string $objectFullname
 * @property-read string $subjectPassport
 * @property-read \App\Models\Program|null $program
 * @property-read \App\Models\Company|null $company
 * @property-read \App\Models\Owner|null $owner
 * @property-read \App\Models\InsuranceObject[]|array|Collection $objects
 * @property-read \App\Models\Subject|null $subject
 * @property-read mixed $ownerCode
 * @property-read mixed $ownerUwLogin
 * @property int|null $uwContractId
 * @mixin Eloquent
 */
class Contract extends BaseModel
{
    use Filterable;

    const STATUS_DRAFT = 1;
    const STATUS_CONFIRMED = 2;
    const TYPE_NS = 5;

    protected $casts = [
        'options' => 'json',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'type' => self::TYPE_NS,
        'status' => self::STATUS_DRAFT,
        'active_from' => null,
        'active_to' => null,
        'signed_at' => null,
        'options' => '',
    ];

    protected $dates = ['active_from', 'active_to', 'signet_at', 'created_at', 'updated_at'];

    protected $fillable = [
        'programCode',
        'company_id',
        'program_id',
        'type',
        'status',
        'active_from',
        'active_to',
        'signed_at',
        'premium',
        'options',
        'mortgageAgreementNumber',
        'isOwnership',
        'mortgageeBank',
        'remainingDebt',
        'ext_id',
        'contractId',
        'uw_contract_id',
    ];

    protected $visible = [
        'id',
        'activeFrom',
        'activeTo',
        'objects',
        'subject',
        'companyCode',
        'signedAt',
        'programName',
        'premium',
        'paymentStatus',
        'policyNumber',
        'trafficSource',
        'contractId',
        'uwContractId',
        'options',
        'calcCoeff',
        'programCode',
        'mortgageAgreementNumber',
        'isOwnership',
        'mortgageeBank',
        'remainingDebt',
        'contractId',
    ];

    protected $appends = [
        'companyCode',
        'programCode',
        'mortgageAgreementNumber',
        'isOwnership',
        'mortgageeBank',
        'uwContractId',
    ];

    public function getCompanyCodeAttribute()
    {
        return $this->company->code ?? null;
    }

    public function getProgramCodeAttribute()
    {

        return $this->program->program_code ?? null;
    }

    public function getMortgageAgreementNumberAttribute()
    {
        return $this->options['mortgageAgreementNumber'] ?? null;
    }

    /**
     * Set belongsTo Program Model.
     */
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Set belongsTo Company Model.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Set hasMany Payment Model.
     */
    public function payment()
    {
        return $this->hasMany(Payment::class, 'contract_id', 'id');
    }

    /**
     * Set belongsTo Owner Model.
     */
    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    /**
     * Set hasMany Objects Model.
     */
    public function objects()
    {
        return $this->hasMany(InsuranceObject::class, 'contract_id');
    }

    /**
     * Set hasMany Objects Model.
     */
    public function subject()
    {
        return $this->hasOne(Subject::class, 'contract_id');
    }

    /**
     * @param $query
     * @param $ownerId
     * @return mixed
     */
    public function scopeOfOwner($query, $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    public function getRouteKeyName()
    {
        return "id";
    }

    public function getContractIdAttribute()
    {
        return $this->attributes['ext_id'];
    }

    public function setContractIdAttribute($value)
    {
        $this->attributes['ext_id'] = $value;
    }

    public function getPremiumAttribute()
    {
        return $this->attributes['premium'];
    }

    public function getRemainingDebtAttribute()
    {
        return $this->attributes['remainingDebt'];
    }

    public function getProgramNameAttribute()
    {
        if (!$this->program) {
            return '';
        }
        return Str::lower($this->program->program_name);
    }

    public function getActiveFromAttribute()
    {
        return $this->attributes['active_from'];
    }

    public function getActiveToAttribute()
    {
        return $this->attributes['active_to'];
    }

    public function getSignedAtAttribute()
    {
        return $this->attributes['signed_at'];
    }

    public function getPaymentStatusAttribute()
    {
        return $this->attributes['status'];
    }

    public function getPolicyNumberAttribute()
    {
        return $this->attributes['number'];
    }

    public function getUwContractIdAttribute()
    {
        return $this->attributes['uw_contract_id'];
    }

    public function getStatusTextAttribute()
    {
        if ($this->status == self::STATUS_CONFIRMED) {
            return 'оплачен';
        } else {
            return 'ожидает оплаты';
        }
    }

    public function getSubjectValueAttribute()
    {
        return $this->subject->value;
    }

    public function getOptionsAttribute()
    {
        return json_decode($this->attributes['options'], true) ;
    }

    public function setMortgageAgreementNumberAttribute($value)
    {
        $options = $this->getAttribute('options') ?: [];
        $options['mortgageAgreementNumber'] = (string)$value;
        $this->setAttribute('options', $options);
    }

    public function setMortgageeBankAttribute($value)
    {
        $options = $this->getAttribute('options') ?: [];
        $options['mortgageeBank'] = (string)$value;
        $this->setAttribute('options', $options);
    }

    public function getMortgageeBankAttribute()
    {
        return $this->getAttribute('options')['mortgageeBank'] ?? '';
    }

    public function setIsOwnershipAttribute($value)
    {
        $options = $this->getAttribute('options') ?: [];
        $options['isOwnership'] = (bool)$value;
        $this->setAttribute('options', $options);
    }

    public function getIsOwnershipAttribute(): bool
    {
        return (bool)($this->getAttribute('options')['isOwnership'] ?? false);
    }

    public function setProgramCodeAttribute($value)
    {
        if ($value) {
            $program = Program::query()
                              ->where('program_code', '=', $value)
                              ->firstOrFail();
            $this->program_id = $program->id;
            $this->company_id = $program->companyId;
        }
    }

    public function setActiveFromAttribute($val)
    {
        $this->attributes['active_from'] = $val;
        $this->setAttribute('signed_at', $val);
    }

    public function setOptionsAttribute($value)
    {
        $this->attributes['options'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

}
