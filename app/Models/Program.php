<?php

namespace App\Models;
use App\Filters\ProgramFilter;
use Illuminate\Database\Eloquent\Builder;
use Strahovka\LaravelFilterable\Filterable;


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
 * @property-read \App\Models\Companie|null $company
 * @property-read string $companyCode
 * @property-read string $companyName
 * @mixin \Eloquent
 */

class Program extends BaseModel
{
    use Filterable;

    const NAME = 'Продукты СК';

    protected $fillable = [
        'company_id',
        'program_code',
        'program_name',
        'description',
        'risks',
        'issues',
        'conditions',
        'is_property',
        'is_life',
        'is_title',
        'is_recommended',
        'is_active',
        'program_uw_code',
        'matrix'
    ];

    protected $casts = [
        'is_property' => 'boolean',
        'is_life' => 'boolean',
        'is_title' => 'boolean',
        'is_recommended' => 'boolean',
        'program_uw_code' => 'integer',
        'is_active' => 'boolean',
        'conditions' => 'array',
        'issues' => 'array',
        'risks' => 'array',
        'programCode' => 'string',
        'matrix' => 'array'
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
        return $this->belongsTo(Companie::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contracts::class);
    }

    public function owners()
    {
        return $this->belongsToMany(Owner::class, 'owners_programs');
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
        $this->attributes['conditions'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Writes Object JSON to field (for Laravel 5.8)
     * @param $value
     */
    public function setMatrixAttribute($value)
    {
        $this->attributes['matrix'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Writes Subject JSON to field (for Laravel 5.8)
     * @param $value
     */
    public function setRisksAttribute($value)
    {
        $this->attributes['risks'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Writes Object JSON to field (for Laravel 5.8)
     * @param $value
     */
    public function setIssuesAttribute($value)
    {
        $this->attributes['issues'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array
     */
    public function getConditionsAttribute()
    {
        return json_decode($this->attributes['conditions'], true);
    }

    /**
     * @return array
     */
    public function getMatrixAttribute()
    {
        return json_decode($this->attributes['matrix'], true);
    }

    /**
     * @return array
     */
    public function getRisksAttribute()
    {
        return json_decode($this->attributes['risks'], true);
    }

    /**
     * @return array
     */
    public function getIssuesAttribute()
    {
        return json_decode($this->attributes['issues'], true);
    }

    public function getOwnerCodeAttribute()
    {
        return isset($this->owners) ? $this->owners->code : '';
    }
}
