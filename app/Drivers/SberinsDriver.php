<?php


namespace App\Drivers;


use App\Drivers\DriverResults\Calculated;
use App\Drivers\DriverResults\CalculatedInterface;
use App\Drivers\DriverResults\CreatedPolicyInterface;
use App\Drivers\DriverResults\PayLinkInterface;
use App\Drivers\Source\Sberbank\Sberbank;
use App\Models\Contracts;
use App\Services\PayService\PayLinks;

class SberinsDriver implements DriverInterface
{

    public function calculate(array $data): CalculatedInterface
    {
        $dataCollect = collect($data);
        $sberbank = new Sberbank();
        $objects = collect($dataCollect->get('objects'));
        $property = collect($objects->get('property'));

        $propertyInsurancePremium = $sberbank->getInsurancePremium(
            $dataCollect->get('programCode'),
            $dataCollect->get('remainingDebt'),
            $property->get('isWooden'));

        return new Calculated(
            null,
            null,
            $propertyInsurancePremium
        );

    }

    public function getPayLink(Contracts $contract, PayLinks $payLinks): PayLinkInterface
    {
        // TODO: Implement getPayLink() method.
    }

    public function createPolicy(Contracts $contract, array $data): CreatedPolicyInterface
    {
        // TODO: Implement createPolicy() method.
    }

    public function printPolicy(Contracts $contract, bool $sample, bool $reset, ?string $filePath = null)
    {
        // TODO: Implement printPolicy() method.
    }

    public function payAccept(Contracts $contract): void
    {
        // TODO: Implement payAccept() method.
    }

    public function sendPolice(Contracts $contract): string
    {
        // TODO: Implement sendPolice() method.
    }

    public function getStatus(Contracts $contract): array
    {
        // TODO: Implement getStatus() method.
    }
}
