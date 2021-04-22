<?php

namespace App\Models;

use Eloquent;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Kyslik\LaravelFilterable\Filterable;

/**
 * App\Models\Contracts
 *
 * @property int $id
 * @property int|null $programId
 * @property int $type
 * @property int $ownerId
 * @property array|null $options
 * @property int $status
 * @property string $statusText
 * @property int $remaining_debt
 * @property int $premium
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
 * @property-read \App\Models\Programs|null $program
 * @property-read \App\Models\Companies|null $company
 * @property-read \App\Models\Owners|null $owner
 * @property-read \App\Models\Objects|null $objects
 * @property-read \App\Models\Subjects|null $subject
 * @property-read mixed $ownerCode
 * @property-read mixed $ownerUwLogin
 * @property int|null $uwContractId
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contracts newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contracts newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contracts query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contracts whereActiveFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contracts whereActiveTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contracts whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contracts whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contracts whereObject($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contracts whereOptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contracts whereProgramId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contracts whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contracts whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contracts whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contracts whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Contracts extends BaseModel
{
    use Filterable;

    const STATUS_DRAFT = 1;
    const STATUS_CONFIRMED = 2;
    const TYPE_NS = 5;

    protected $casts = [
        'options' => 'json'
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
        'remaining_debt',
        'premium',
        'active_from',
        'active_to',
        'signed_at',
        'program_id',
        'company_id',
        'uw_contract_id',
        'options',
    ];

    protected $visible = [
        'id',
        'activeFrom',
        'activeTo',
        'objectsValue',
        'subjectValue',
        'objectFullName',
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
    ];

    protected $appends = [
        'activeFrom',
        'objectsValue',
        'objectFullName',
        'activeTo',
        'companyCode',
        'signedAt',
        'premium',
        'programName',
        'paymentStatus',
        'policyNumber',
        'trafficSource',
        'contractId',
        'uwContractId',
        'object',
        'subject',
        'options',
        'calcCoeff',
    ];

    /**
     * Set belongsTo Program Model.
     */
    public function program()
    {
        return $this->belongsTo(Programs::class);
    }

    /**
     * Set belongsTo Company Model.
     */
    public function company()
    {
        return $this->belongsTo(Companies::class);
    }

    /**
     * Set hasMany Payment Model.
     */
    public function payment()
    {
        return $this->hasMany(Payments::class, 'contract_id', 'id');
    }

    /**
     * Set belongsTo Owner Model.
     */
    public function owner()
    {
        return $this->belongsTo(Owners::class);
    }

    /**
     * Set hasMany Objects Model.
     */
    public function objects()
    {
        return $this->hasMany(Objects::class, 'contract_id');
    }

    /**
     * Set hasMany Objects Model.
     */
    public function subject()
    {
        return $this->hasOne(Subjects::class, 'contract_id');
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
        return $this->attributes['id'];
    }

    public function getCompanyCodeAttribute()
    {
        if (!$this->company) {
            return '';
        }
        return $this->company->code;
    }

    public function getPremiumAttribute()
    {
        return $this->attributes['premium'];
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

    public function getTrafficSourceAttribute()
    {
        $options = json_decode($this->attributes['options'], true);
        return isset($options['trafficSource']) ? $options['trafficSource'] : '';
    }

    public function getObjectsValueAttribute()
    {
        $result = [];
        if($this->objects) {
            foreach ($this->objects as $object) {
                $result[] = $object->value;
            }
        }
        return $result;
    }

    public function getSubjectValueAttribute()
    {
        return $this->subject->value;
    }

    public function getOptionsAttribute()
    {
        return json_decode($this->attributes['options'], true);
    }

    /**
     * Get joined fullname from subject JSON.
     * @return string
     */
    public function getSubjectFullnameAttribute()
    {
        if (!$this->subject()) {
            return '';
        }
        $pieces = [
            $this->subjectValue['lastName'],
            $this->subjectValue['firstName'],
            $this->subjectValue['middleName'] ?? ''
        ];
        return implode(' ', $pieces);
    }

    /**
     * Get joined passport serie and number from subject JSON.
     * @return string
     */
    public function getSubjectPassportAttribute()
    {
        if (empty($this->subjectValue)) {
            return '';
        }
        $pieces = [
            $this->subjectValue['docSeries'],
            $this->subjectValue['docNumber']
        ];
        return implode(' ', $pieces);
    }

    /**
     * Get joined fullname from subject JSON.
     * @return array
     */
    public function getObjectFullnameAttribute()
    {
        $result = [];
        if (empty($this->objectsValue)) {
            return '';
        }
        foreach ($this->objectsValue as $obj)
        {
            $pieces = [
                $obj['lastName'],
                $obj['firstName'],
                $obj['middleName'] ?? ''
            ];
            $result[] = implode(' ', $pieces);
        }
        return $result;
    }

    public function setOptionsAttribute($value)
    {
        $this->attributes['options'] = $value;
    }

    /**
     * @return string
     */
    public function getOwnerCodeAttribute()
    {
        return $this->owner ? $this->owner->code : '';
    }

    /**
     * @return string
     */
    public function getOwnerUwLoginAttribute()
    {
        return $this->owner ? $this->owner->uwLogin : '';
    }

    /**
     * @return array
     */
    public function getCalcCoeffAttribute()
    {
        return json_decode($this->attributes['calc_coeff']);
    }

    public function setCalcCoeffAttribute($value)
    {
        $this->attributes['calc_coeff'] = $value;
    }

    public function getSubjectAddressAttribute()
    {
        if (!$this->subjectValue || !is_array($this->subjectValue)) {
            return '';
        }
        return implode(', ', [Arr::get($this->subjectValue, 'city'), Arr::get($this->subjectValue, 'street'), Arr::get($this->subjectValue, 'house'), Arr::get($this->subjectValue, 'block'), Arr::get($this->subjectValue, 'apartment')]);
    }
}
