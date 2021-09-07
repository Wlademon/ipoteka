<?php


namespace App\Drivers\DriverResults;

/**
 * Class CreatedPolicy
 *
 * @package App\Drivers\DriverResults
 */
class CreatedPolicy implements CreatedPolicyInterface
{

    protected ?int $contractId;
    protected ?float $lifePremium;
    protected ?float $propertyPremium;
    protected ?string $lifePolicyNumber;
    protected ?string $propertyPolicyNumber;
    protected ?string $lifePolicyId;
    protected ?string $propertyPolicyId;

    public function __construct(
        ?int $contractId,
        ?string $lifePolicyId,
        ?string $propertyPolicyId,
        ?float $lifePremium = null,
        ?float $propertyPremium = null,
        ?string $lifePolicyNumber = null,
        ?string $propertyPolicyNumber = null

    )
    {
        $this->contractId = $contractId;
        $this->propertyPremium = $propertyPremium;
        $this->lifePremium = $lifePremium;
        $this->lifePolicyNumber = $lifePolicyNumber;
        $this->propertyPolicyNumber = $propertyPolicyNumber;
        $this->lifePolicyId = $lifePolicyId;
        $this->propertyPolicyId = $propertyPolicyId;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'contractId'           => $this->contractId,
            'premiumSum'           => $this->getPremiumSum(),
            'lifePremium'          => (float)$this->lifePremium,
            'propertyPremium'      => (float)$this->propertyPremium,
            'lifePolicyNumber'     => $this->lifePolicyNumber,
            'propertyPolicyNumber' => $this->propertyPolicyNumber,
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
     * @param $contractId int
     */
    public function setContractId(int $contractId): void
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
    public function setLifePremium(float $lifePremium): void
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
    public function setPropertyPremium(float $propertyPremium): void
    {
        $this->propertyPremium = $propertyPremium;
    }

    /**
     * @return string|null
     */
    public function getLifePolicyNumber(): ?string
    {
        return $this->lifePolicyNumber;
    }

    /**
     * @param $lifePolicyNumber string
     */
    public function setLifePolicyNumber(string $lifePolicyNumber): void
    {
        $this->lifePolicyNumber = $lifePolicyNumber;
    }


    /**
     * @return string|null
     */
    public function getPropertyPolicyNumber(): ?string
    {
        return $this->propertyPolicyNumber;
    }

    /**
     * @param $propertyPolicyNumber string|null
     */
    public function setPropertyPolicyNumber(string $propertyPolicyNumber): void
    {
        $this->propertyPolicyNumber = $propertyPolicyNumber;
    }

    public function getPropertyPolicyId(): ?string
    {
        return $this->propertyPolicyId;
    }

    /**
     * @param $lifePolicyNumber string|null
     */
    public function setPropertyPolicyId(string $propertyPolicyId): void
    {
        $this->propertyPolicyId = $propertyPolicyId;
    }

    public function getLifePolicyId(): ?string
    {
        return $this->lifePolicyId;
    }

    /**
     * @param $lifePolicyId string|null
     */
    public function setLifePolicyId(string $lifePolicyId): void
    {
        $this->lifePolicyId = $lifePolicyId;
    }
}
