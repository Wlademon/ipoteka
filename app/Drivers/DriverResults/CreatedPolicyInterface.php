<?php

namespace App\Drivers\DriverResults;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Interface CreatedPolicyInterface
 * @package App\Drivers\DriverResults
 */
interface CreatedPolicyInterface extends Arrayable
{
    /**
     * @param  int  $value
     *
     * @return mixed
     */
    public function setContractId(int $value): void;

    /**
     * Идентификатор сделки
     * @return int
     */
    public function getContractId(): ?int;

    /**
     * Общая премия по договору
     * @return float
     */
    public function getPremiumSum(): float;

    /**
     * Премия по риску страхования жизни
     * @return float
     */
    public function getLifePremium(): ?float;

    /**
     * Премия по риску страхования недвижимого имущества
     *
     * @return float|null
     */
    public function getPropertyPremium(): ?float;

    /**
     * Номер договора по риску страхования жизни
     *
     * @return string|null
     */
    public function getLifePolicyNumber(): ?string;

    /**
     * Номер договора по риску страхования недвижимого имущества
     *
     * @return string|null
     */
    public function getPropertyPolicyNumber(): ?string;

    /**
     * Идентификатор во внешней системе
     * @return string
     */
    public function getPropertyPolicyId(): ?string;

    /**
     * Идентификатор во внешней системе
     * @return string
     */
    public function getLifePolicyId(): ?string;
}
