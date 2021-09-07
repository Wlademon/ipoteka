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
    public function getContractId(): ?int;

    public function getPremiumSum(): float;

    public function getLifePremium(): ?float;

    public function getPropertyPremium(): ?float;
}
