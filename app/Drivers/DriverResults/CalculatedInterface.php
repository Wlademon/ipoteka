<?php

namespace App\Drivers\DriverResults;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Interface CalculatedInterface
 *
 * @package App\Drivers\DriverResults
 */
interface CalculatedInterface extends Arrayable
{
    /**
     * @return int|null
     */
    public function getContractId(): ?int;

    /**
     * @return float
     */
    public function getPremiumSum(): float;

    /**
     * @return float|null
     */
    public function getLifePremium(): ?float;

    /**
     * @return float|null
     */
    public function getPropertyPremium(): ?float;
}
