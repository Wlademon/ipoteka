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
use App\Models\Contracts;
use App\Models\Program;
use Arr;
use Throwable;

/**
 * Class SberinsDriver
 * @package App\Drivers
 */
class SberinsDriver implements DriverInterface
{

    use PrintPdfTrait;
    use DriverTrait;

    /**
     * @param array $data
     * @return CalculatedInterface
     */
    public function calculate(array $data): CalculatedInterface
    {
        $propertyInsurancePremium = $this->getInsurancePremium(
            Arr::get($data, 'programCode'),
            Arr::get($data, 'remainingDebt'),
            Arr::get($data, 'objects.property.isWooden'),
        );

        return new Calculated(
            null,
            null,
            $propertyInsurancePremium
        );

    }

    /**
     * @param Contracts $contract
     * @param array $data
     * @return CreatedPolicyInterface
     * @throws \App\Exceptions\Services\PolicyServiceException
     */
    public function createPolicy(Contracts $contract, array $data): CreatedPolicyInterface
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
            $contract->id,
            null,
            '',
            null,
            $propertyPremium ?? null,
            null,
            $propertyPolicyNumber,
        );
    }

    /**
     * @param Contracts $contract
     * @param bool $sample
     * @param bool $reset
     * @param string|null $filePath
     * @return string
     */
    public function printPolicy(
        Contracts $contract,
        bool $sample,
        bool $reset = false,
        ?string $filePath = null
    ): string {
        $sampleText = $sample ? '_sample' : '';
        if (!$filePath) {
            $filename = public_path() . '/' . config('mortgage.pdf.path') . sha1(
                    $contract->id . $contract->number
                ) . $sampleText . '.pdf';
        } else {
            $filename = $filePath;
        }
        if (!file_exists($filename) || $reset) {
            $filename = self::generatePdf($contract, $sample, $filename);
        }

        return self::generateBase64($filename);
    }

    /**
     * @param Contracts $contract
     */
    public function payAccept(Contracts $contract): void
    {
        return;
    }

    /**
     * @param string $programCode
     * @param int $remainingDebt
     * @param bool $isWooden
     * @return int
     */
    public function getInsurancePremium(string $programCode, int $remainingDebt, bool $isWooden): int
    {
        $matrix = Program::query()->where('program_code', $programCode)->first('matrix')->matrix;
        $woodenRate = Arr::get($matrix, 'tariff.wooden.percent', 1);
        $stoneRate = Arr::get($matrix, 'tariff.stone.percent', 1);

        if (!$isWooden) {
            return $remainingDebt / 100 * $stoneRate;
        }

        return $remainingDebt / 100 * $woodenRate;
    }

    /**
     * @param Contracts $contract
     * @return array
     */
    protected function getDataForPolicyNumber(Contracts $contract): array
    {
        return [
            'product_code' => 'mortgage',
            'program_code' => $contract->program->programCode,
            'bso_owner_code' => $contract->company->code,
            'bso_receiver_code' => 'STRAHOVKA',
            'count' => 1,
        ];
    }
}
