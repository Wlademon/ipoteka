<?php


namespace App\Drivers;

use App\Drivers\DriverResults\Calculated;
use App\Drivers\DriverResults\CalculatedInterface;
use App\Drivers\DriverResults\CreatedPolicy;
use App\Drivers\DriverResults\CreatedPolicyInterface;
use App\Drivers\Traits\DriverTrait;
use App\Helpers\Helper;
use App\Models\Contracts;
use App\Printers\PolicyPrinter;
use Illuminate\Support\Facades\DB;

class SberinsDriver implements DriverInterface
{

    use DriverTrait;

    public function calculate(array $data): CalculatedInterface
    {
        $dataCollect = collect($data);
        $objects = collect($dataCollect->get('objects'));
        $property = collect($objects->get('property'));

        $propertyInsurancePremium = $this->getInsurancePremium(
            $dataCollect->get('programCode'),
            $dataCollect->get('remainingDebt'),
            $property->get('isWooden'));

        return new Calculated(
            null,
            null,
            $propertyInsurancePremium
        );

    }

    /**
     * @throws \App\Exceptions\Services\PolicyServiceException
     */
    public function createPolicy(Contracts $contract, array $data): CreatedPolicyInterface
    {

        $propertyPremium = $this->calculate($data)->getPropertyPremium();
        $contract->premium = $propertyPremium;
        $contract->save();
        $res = Helper::getPolicyNumber($this->getDataForPolicyNumber($contract));
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

    public function printPolicy(Contracts $contract, bool $sample, bool $reset, ?string $filePath = null)
    {
        $pdfPaths = [];
        $policyPrinter = new PolicyPrinter($pdfPaths);
        $template = mb_strtolower($contract->program->companyCode);
        $filename = $policyPrinter->getFilenameWithDir($contract, $sample);
        \PDF::setOptions(
            [
                'logOutputFile' => storage_path('logs/anti-mite-generate-pdf.htm'),
                'tempDir' => $pdfPaths['tmp'],
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'dpi' => 96,
            ]
        )->loadView("templates.$template", compact('contract', 'sample'))
            ->save($filename);

        return $filename;
    }


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
        $query = DB::table('programs')->where('program_code', $programCode)->first('matrix');
        $decodeMatrix = (json_decode($query->matrix, true));

        $woodenRate = $decodeMatrix['tariff']['wooden']['percent'] ?? 1;
        $stoneRate = $decodeMatrix['tariff']['stone']['percent'] ?? 1;

        if (!$isWooden) {
            return $remainingDebt * $stoneRate;
        }

        return $remainingDebt * $woodenRate;
    }

    protected function getDataForPolicyNumber(Contracts $contract): array
    {
        return [
            'product_code' => 'mortgage',
            'program_code' => $contract->program->programCode,
            'bso_owner_code' => $contract->company->code,
            'bso_receiver_code' => 'STRAHOVKA', // Код получателя БСО'bso_receiver_code' => 'STRAHOVKA'
            "count" => 1,
        ];
    }
}
