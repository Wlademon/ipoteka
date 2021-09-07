<?php

namespace App\Models;

use App\Filters\ProgramFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Strahovka\LaravelFilterable\Filterable;

/**
 * App\Models\Program
 *
 *
 *
 * @property int                             $id
 * @property string                          $companyId
 * @property string                          $programCode
 * @property string                          $programUwCode
 * @property string                          $programName
 * @property string                          $description
 * @property array                           $risks
 * @property array                           $issues
 * @property array|object                    $conditions
 * @property array                           $matrix
 * @property float                           $insuredSum
 * @property string                          $isChild
 * @property string                          $isAdult
 * @property string                          $isFamily
 * @property string                          $isActive
 * @property string                          $ownerCode
 * @property \Illuminate\Support\Carbon|null $createdAt
 * @property \Illuminate\Support\Carbon|null $updatedAt
 * @property \Illuminate\Support\Carbon|null $deletedAt
 * @property-read \App\Models\Company|null   $company
 * @property-read string                     $companyCode
 * @property-read string                     $companyName
 * @mixin \Eloquent
 */
class Program extends BaseModel
{
    use Filterable;

    public const NAME = 'Продукты СК';
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
        'matrix',
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
        'matrix' => 'array',
    ];
    protected $hidden = [
        'matrix',
    ];

    /**
     * @param  Builder        $query
     * @param  ProgramFilter  $filter
     *
     * @return Builder
     * @throws \Strahovka\LaravelFilterable\Exceptions\MissingBuilderInstance
     */
    public function scopeFilter(Builder $query, ProgramFilter $filter): Builder
    {
        return $filter->apply($query);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(Owner::class, 'owners_programs');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', 1);
    }

    public function scopeOfCompany(Builder $query, string $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function getCompanyIdAttribute(): ?int
    {
        return $this->attributes['company_id'];
    }

    public function getCompanyCodeAttribute(): string
    {
        return $this->company->code ?? '';
    }

    public function getCompanyNameAttribute(): string
    {
        return $this->company->name ?? '';
    }

    /**
     * Writes Object JSON to field (for Laravel 5.8)
     *
     * @param  array  $value
     *
     * @throws \JsonException
     */
    public function setConditionsAttribute(array $value): void
    {
        $this->attributes['conditions'] = json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * Writes Object JSON to field (for Laravel 5.8)
     *
     * @param  array  $value
     *
     * @throws \JsonException
     */
    public function setMatrixAttribute(array $value): void
    {
        $this->attributes['matrix'] = json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * Writes Subject JSON to field (for Laravel 5.8)
     *
     * @param  array  $value
     *
     * @throws \JsonException
     */
    public function setRisksAttribute(array $value): void
    {
        $this->attributes['risks'] = json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * Writes Object JSON to field (for Laravel 5.8)
     *
     * @param  array  $value
     *
     * @throws \JsonException
     */
    public function setIssuesAttribute(array $value): void
    {
        $this->attributes['issues'] = json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * @return array
     * @throws \JsonException
     */
    public function getConditionsAttribute(): array
    {
        return json_decode($this->attributes['conditions'], true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array
     * @throws \JsonException
     */
    public function getMatrixAttribute(): array
    {
        return json_decode($this->attributes['matrix'], true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array
     * @throws \JsonException
     */
    public function getRisksAttribute(): array
    {
        return json_decode($this->attributes['risks'], true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array
     * @throws \JsonException
     */
    public function getIssuesAttribute(): array
    {
        return json_decode($this->attributes['issues'], true, 512, JSON_THROW_ON_ERROR);
    }

    public function getOwnerCodeAttribute(): string
    {
        return isset($this->owners) ? $this->owners->code : '';
    }
}
