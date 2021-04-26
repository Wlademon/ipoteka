<?php

namespace App\Drivers\DriverResults;

/**
 * Class Calculated
 * @package App\Drivers\DriverResults
 */
class Calculated implements CalculatedInterface
{
    protected ?int $contractId;
    protected ?float $propertyPremium;
    protected ?float $lifePremium;
    
    /**
     * Calculated constructor.
     * @param int|null $contractId
     * @param float|null $lifePremium
     * @param float|null $propertyPremium
     */
    public function __construct(?int$contractId, ?float $lifePremium = null, ?float $propertyPremium = null)
    {
        $this->contractId = $contractId;
        $this->propertyPremium = $propertyPremium;
        $this->lifePremium = $lifePremium;
    }
    
    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return [
            'contractId' => $this->contractId,
            'premiumSum' => $this->getPremiumSum(),
            'lifePremium' => (float)$this->lifePremium,
            'propertyPremium' => (float)$this->propertyPremium,
        ];
    }
    
    /**
     * @return int|null
     */
    public function getContractId(): ?int
    {
        return $this->contractId;
    }
    
    /**
     * @param $contractId int|null
     */
    public function setContractId(?int $contractId)
    {
        $this->contractId = $contractId;
    }
    
    public function getPremiumSum(): float
    {
        return ((float)$this->getLifePremium()) + ((float)$this->getPropertyPremium());
    }
    
    /**
     * @return float|null
     */
    public function getLifePremium(): ?float
    {
        return $this->lifePremium;
    }
    
    /**
     * @param $lifePremium float|null
     */
    public function setLifePremium(?float $lifePremium)
    {
        $this->lifePremium = $lifePremium;
    }
    
    /**
     * @return float|null
     */
    public function getPropertyPremium(): ?float
    {
        return $this->propertyPremium;
    }
    
    /**
     * @param $propertyPremium float|null
     */
    public function setPropertyPremium(?float $propertyPremium)
    {
        $this->propertyPremium = $propertyPremium;
    }
}
