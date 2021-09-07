<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
 * @property int $ext_id
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

    public const STATUS_DRAFT = 1;
    public const STATUS_CONFIRMED = 2;
    public const TYPE_NS = 5;

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

    public function getCompanyCodeAttribute(): ?string
    {
        return $this->company->code ?? null;
    }

    public function getProgramCodeAttribute(): ?string
    {

        return $this->program->program_code ?? null;
    }

    /**
     * @return mixed|null
     */
    public function getMortgageAgreementNumberAttribute(): ?string
    {
        return $this->options['mortgageAgreementNumber'] ?? null;
    }

    /**
     * Set belongsTo Program Model.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Set belongsTo Company Model.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Set hasMany Payment Model.
     */
    public function payment(): HasMany
    {
        return $this->hasMany(Payment::class, 'contract_id', 'id');
    }

    /**
     * Set belongsTo Owner Model.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    /**
     * Set hasMany Objects Model.
     */
    public function objects(): HasMany
    {
        return $this->hasMany(InsuranceObject::class, 'contract_id');
    }

    /**
     * Set hasMany Objects Model.
     */
    public function subject(): HasOne
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

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return "id";
    }

    /**
     * @return mixed
     */
    public function getContractIdAttribute(): string
    {
        return $this->attributes['ext_id'];
    }

    /**
     * @param  string  $value
     */
    public function setContractIdAttribute(string $value): void
    {
        $this->attributes['ext_id'] = $value;
    }

    /**
     * @return float|null
     */
    public function getPremiumAttribute(): ?float
    {
        return $this->attributes['premium'];
    }

    /**
     * @return float|null
     */
    public function getRemainingDebtAttribute(): ?float
    {
        return $this->attributes['remainingDebt'];
    }

    /**
     * @return string
     */
    public function getProgramNameAttribute(): string
    {
        if (!$this->program) {
            return '';
        }

        return Str::lower($this->program->program_name);
    }

    /**
     * @return mixed
     */
    public function getActiveFromAttribute()
    {
        return $this->attributes['active_from'];
    }

    /**
     * @return mixed
     */
    public function getActiveToAttribute()
    {
        return $this->attributes['active_to'];
    }

    /**
     * @return mixed
     */
    public function getSignedAtAttribute()
    {
        return $this->attributes['signed_at'];
    }

    /**
     * @return mixed
     */
    public function getPaymentStatusAttribute(): string
    {
        return $this->attributes['status'];
    }

    /**
     * @return mixed
     */
    public function getPolicyNumberAttribute(): string
    {
        return $this->attributes['number'];
    }

    /**
     * @return mixed
     */
    public function getUwContractIdAttribute(): ?string
    {
        return $this->attributes['uw_contract_id'];
    }

    /**
     * @return string
     */
    public function getStatusTextAttribute(): string
    {
        if ($this->status === self::STATUS_CONFIRMED) {
            return 'оплачен';
        } else {
            return 'ожидает оплаты';
        }
    }

    /**
     * @return mixed
     */
    public function getSubjectValueAttribute()
    {
        return $this->subject->value;
    }

    /**
     * @return mixed
     * @throws \JsonException
     */
    public function getOptionsAttribute()
    {
        return json_decode($this->attributes['options'], true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param  string  $value
     */
    public function setMortgageAgreementNumberAttribute(string $value): void
    {
        $options = $this->getAttribute('options') ?: [];
        $options['mortgageAgreementNumber'] = $value;
        $this->setAttribute('options', $options);
    }

    /**
     * @param  string  $value
     */
    public function setMortgageeBankAttribute(string $value): void
    {
        $options = $this->getAttribute('options') ?: [];
        $options['mortgageeBank'] = $value;
        $this->setAttribute('options', $options);
    }

    /**
     * @return mixed|string
     */
    public function getMortgageeBankAttribute(): string
    {
        return $this->getAttribute('options')['mortgageeBank'] ?? '';
    }

    /**
     * @param  bool  $value
     */
    public function setIsOwnershipAttribute(bool $value): void
    {
        $options = $this->getAttribute('options') ?: [];
        $options['isOwnership'] = $value;
        $this->setAttribute('options', $options);
    }

    /**
     * @return bool
     */
    public function getIsOwnershipAttribute(): bool
    {
        return (bool)($this->getAttribute('options')['isOwnership'] ?? false);
    }

    /**
     * @param  string|null  $value
     */
    public function setProgramCodeAttribute(?string $value): void
    {
        if ($value) {
            $program = Program::query()
                              ->where('program_code', '=', $value)
                              ->firstOrFail();
            $this->program_id = $program->id;
            $this->company_id = $program->companyId;
        }
    }

    /**
     * @param $val
     */
    public function setActiveFromAttribute($val): void
    {
        $this->attributes['active_from'] = $val;
        $this->setAttribute('signed_at', $val);
    }

    /**
     * @param  array  $value
     *
     * @throws \JsonException
     */
    public function setOptionsAttribute(array $value): void
    {
        $this->attributes['options'] = json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
        );
    }

}
