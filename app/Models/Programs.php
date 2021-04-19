<?php

namespace App\Models;
use App\Filters\ProgramFilter;
use Illuminate\Database\Eloquent\Builder;
use Kyslik\LaravelFilterable\Filterable;


/**
 * App\Models\Program
 *
 *
 *
 * @property int $id
 * @property string $companyId
 * @property string $programCode
 * @property string $programUwCode
 * @property string $programName
 * @property string $description
 * @property array $risks
 * @property array $issues
 * @property array|object $conditions
 * @property array $matrix
 * @property float $insuredSum
 * @property string $isChild
 * @property string $isAdult
 * @property string $isFamily
 * @property string $isActive
 * @property string $ownerCode
 * @property \Illuminate\Support\Carbon|null $createdAt
 * @property \Illuminate\Support\Carbon|null $updatedAt
 * @property \Illuminate\Support\Carbon|null $deletedAt
 * @property-read \App\Models\Companies|null $company
 * @property-read string $companyCode
 * @property-read string $companyName
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Programs newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Programs newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Programs ofCompany($companyId)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Programs query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Programs whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Programs whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Programs whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Programs whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Programs whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Programs whereInsuredSum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Programs whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Programs whereIsSport($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Programs whereIsChild($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Programs whereIssues($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Programs whereRisks($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Programs whereConditions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Programs whereProgramCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Programs whereProgramName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Programs whereUpdatedAt($value)
 * @mixin \Eloquent
 */

class Programs extends BaseModel
{
    use Filterable;

    const NAME = 'Продукты СК';

    protected $fillable = [
        'company_id',
        'program_code',
        'program_uw_code',
        'program_name',
        'description',
        'risks',
        'issues',
        'conditions',
        'matrix',
        'insured_sum',
        'is_child',
        'is_adult',
        'is_family',
        'is_active',
    ];

    protected $casts = [
        'conditions' => 'array',
        'matrix' => 'array',
        'issues' => 'array',
        'risks' => 'array',
        'programCode' => 'string',
    ];

    protected $visible = [
        'id',
        'matrix',
        'companyCode',
        'companyId',
        'companyName',
        'isChild',
        'isAdult',
        'isFamily',
        'isActive',
        'insuredSum',
        'premium',
        'programCode',
        'programName',
        'description',
        'risks',
        'issues',
        'conditions',
    ];

    protected $appends = [
        'companyCode',
        'companyId',
        'companyName',
        'insuredSum',
        'premium',
        'isChild',
        'isAdult',
        'isFamily',
        'programCode',
        'programName',
    ];

    protected $hidden = [
        'matrix',
    ];

    public function scopeFilter(Builder $query, ProgramFilter $filters){
        return $filters->apply($query);
    }

    /**
     * Get the company.
     */
    public function company()
    {
        return $this->belongsTo(Companies::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contracts::class);
    }

    public function owners()
    {
        return $this->belongsToMany(Owners::class, 'owners_programs');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * @param $query
     * @param $companyId
     * @return mixed
     */
    public function scopeOfCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * @return string
     */
    public function getProgramNameAttribute()
    {
        return $this->attributes['program_name'];
    }

    /**
     * @return string
     */
    public function getProgramCodeAttribute()
    {
        return $this->attributes['program_code'];
    }

    /**
     * @return string
     */
    public function getProgramUwCodeAttribute()
    {
        return $this->attributes['program_uw_code'];
    }

    /**
     * @return string
     */
    public function getPremiumAttribute()
    {
        return isset($this->attributes['matrix']) ? json_decode($this->attributes['matrix'])->tariff->premium : null;
    }

    /**
     * @return string
     */
    public function getInsuredSumAttribute()
    {
        return $this->attributes['insured_sum'] ?? null;
    }


    /**
     * @return string
     */
    public function getIsChildAttribute()
    {
        return $this->attributes['is_child'];
    }

    /**
     * @return string
     */
    public function getIsAdultAttribute()
    {
        return $this->attributes['is_adult'];
    }

    /**
     * @return string
     */
    public function getIsFamilyAttribute()
    {
        return $this->attributes['is_family'];
    }

    /**
     * @return string
     */
    public function getIsActiveAttribute()
    {
        return $this->attributes['is_active'];
    }

    /**
     * @return string
     */
    public function getCompanyIdAttribute()
    {
        return $this->attributes['company_id'];
    }

    /**
     * @return string
     */
    public function getCompanyCodeAttribute()
    {
        if ($this->company) {
            return $this->company->code;
        }
        return '';
    }

    /**
     * @return string
     */
    public function getCompanyNameAttribute()
    {
        if ($this->company) {
            return $this->company->name;
        }
        return '';
    }

    /**
     * Writes Object JSON to field (for Laravel 5.8)
     * @param $value
     */
    public function setConditionsAttribute($value)
    {
        $this->attributes['conditions'] = json_encode($value);
    }

    /**
     * Writes Object JSON to field (for Laravel 5.8)
     * @param $value
     */
    public function setMatrixAttribute($value)
    {
        $this->attributes['matrix'] = json_encode($value);
    }

    /**
     * Writes Subject JSON to field (for Laravel 5.8)
     * @param $value
     */
    public function setRisksAttribute($value)
    {
        $this->attributes['risks'] = json_encode($value);
    }

    /**
     * Writes Object JSON to field (for Laravel 5.8)
     * @param $value
     */
    public function setIssuesAttribute($value)
    {
        $this->attributes['issues'] = json_encode($value);
    }

    /**
     * @return array
     */
    public function getConditionsAttribute()
    {
        return json_decode($this->attributes['conditions']);
    }

    /**
     * @return array
     */
    public function getMatrixAttribute()
    {
        return json_decode($this->attributes['matrix']);
    }

    /**
     * @return array
     */
    public function getRisksAttribute()
    {
        return json_decode($this->attributes['risks']);
    }

    /**
     * @return array
     */
    public function getIssuesAttribute()
    {
        return json_decode($this->attributes['issues']);
    }

    public function getOwnerCodeAttribute()
    {
        return isset($this->owners) ? $this->owners->code : '';
    }
}
