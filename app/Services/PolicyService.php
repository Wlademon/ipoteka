<?php

namespace App\Services;

use App\Http\Requests\Request;
use Exception;
use App\Models\Contracts;

class PolicyService extends Service
{
    /**
     * @param Request $request
     * @param DriverService $driver
     * @return array
     * @throws Exception
     * @internal param array $data
     */
    public function savePolicy(Request $request, DriverService $driver)
    {
        return $driver->savePolicy($request->validated());
    }

    /**
     * @param Contracts $contract
     * @return array
     * @internal param Contracts $contract
     */
    public function acceptPayment(Contracts $contract): array
    {
        return (new DriverService())->acceptPayment($contract);
    }

    /**
     * @param $data array
     * @return string
     * @internal param Contracts $contract
     */
    public function getPolicyPrint($data): string
    {
        $contract = $data['contract'];
        $sample = filter_var(
            isset($data['sample']) ? $data['sample'] : false,
            FILTER_VALIDATE_BOOL
        );

        return (new DriverService())->printPdf($contract, $sample);
    }

    /**
     * @param Contracts $contract
     * @return array
     * @throws Exception
     */
    public function sendMail(Contracts $contract): array
    {
        return (new DriverService())->sendMail($contract);
    }

    /**
     * @param Contracts $contract
     * @return array|null
     */
    public function getStatus(Contracts $contract): array
    {
        return (new DriverService())->getStatus($contract);
    }
}
