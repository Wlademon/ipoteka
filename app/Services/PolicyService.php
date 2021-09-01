<?php

namespace App\Services;

use App\Http\Requests\Request;
use Exception;
use App\Models\Contract;

/**
 * Class PolicyService
 *
 * @package App\Services
 */
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
     * @param $data array
     * @return string
     * @throws \App\Exceptions\Services\DriverServiceException
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
     * @param Contract $contract
     * @return array
     * @throws Exception
     */
    public function sendMail(Contract $contract): array
    {
        return (new DriverService())->sendMail($contract);
    }

    /**
     * @param Contract $contract
     * @return array|null
     * @throws \App\Exceptions\Services\DriverServiceException
     */
    public function getStatus(Contract $contract): array
    {
        return (new DriverService())->getStatus($contract);
    }
}
