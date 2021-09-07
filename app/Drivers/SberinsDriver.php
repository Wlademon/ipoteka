<?php

namespace App\Drivers;

use App\Drivers\DriverResults\Calculated;
use App\Drivers\DriverResults\CalculatedInterface;
use App\Drivers\DriverResults\CreatedPolicy;
use App\Drivers\DriverResults\CreatedPolicyInterface;
use App\Drivers\Traits\DriverTrait;
use App\Drivers\Traits\PrintPdfTrait;
use App\Exceptions\Drivers\SberinsException;
use App\Helpers\Helper;
use App\Models\Contract;
use App\Models\Program;
use Illuminate\Support\Arr;
use Throwable;

/**
 * Class SberinsDriver
 *
 * @package App\Drivers
 */
class SberinsDriver implements LocalDriverInterface, LocalPaymentDriverInterface, DriverInterface
{
    use PrintPdfTrait;
    use DriverTrait;

    /**
     * @param  array  $data
     *
     * @return CalculatedInterface
     */
    public function calculate(array $data): CalculatedInterface
    {
        $propertyInsurancePremium = $this->getInsurancePremium(
            Arr::get($data, 'programCode'),
            Arr::get($data, 'remainingDebt'),
            Arr::get($data, 'objects.property.isWooden'),
        );

        return new Calculated($data['contractId'] ?? null, null, $propertyInsurancePremium);
    }

    /**
     * @param  Contract  $contract
     * @param  array     $data
     *
     * @return CreatedPolicyInterface
     * @throws SberinsException
     */
    public function createPolicy(Contract $contract, array $data): CreatedPolicyInterface
    {
        $propertyPremium = $this->calculate($data)->getPropertyPremium();
        $contract->premium = $propertyPremium;

        try {
            $res = Helper::getPolicyNumber($this->getDataForPolicyNumber($contract));
        } catch (Throwable $throwable) {
            throw new SberinsException($throwable->getMessage(), 0, $throwable);
        }

        $propertyPolicyNumber = $res->data->bso_numbers[0];

        return new CreatedPolicy(
            $contract->id, null, null, null, $propertyPremium, null, $propertyPolicyNumber,
        );
    }

    /**
     * @param  Contract  $contract
     */
    public function payAccept(Contract $contract): void
    {
        return;
    }

    /**
     * @param  string  $programCode
     * @param  int     $remainingDebt
     * @param  bool    $isWooden
     *
     * @return int
     */
    public function getInsurancePremium(
        string $programCode,
        int $remainingDebt,
        bool $isWooden
    ): int {
        $matrix = Program::query()->where('program_code', $programCode)->first('matrix')->matrix;
        $woodenRate = Arr::get($matrix, 'tariff.wooden.percent', 1);
        $stoneRate = Arr::get($matrix, 'tariff.stone.percent', 1);

        if (!$isWooden) {
            return $remainingDebt / 100 * $stoneRate;
        }

        return $remainingDebt / 100 * $woodenRate;
    }

    /**
     * @param  Contract  $contract
     *
     * @return array
     */
    protected function getDataForPolicyNumber(Contract $contract): array
    {
        return [
            'product_code' => 'mortgage',
            'program_code' => $contract->program->programCode,
            'bso_owner_code' => $contract->company->code,
            'bso_receiver_code' => 'STRAHOVKA',
            'count' => 1,
        ];
    }

    public static function code(): string
    {
        return 'sberins';
    }
}
