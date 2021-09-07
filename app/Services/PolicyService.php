<?php

namespace App\Services;

use App\Exceptions\Services\DriverServiceException;
use App\Http\Requests\Request;
use App\Printers\PolicyPrinter;
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
     *
     * @return array
     * @throws Exception|\Throwable
     * @internal param array $data
     */
    public function savePolicy(Request $request, DriverService $driver): array
    {
        return $driver->savePolicy($request->validated());
    }

    /**
     * @param $data array
     *
     * @return string
     * @throws DriverServiceException
     * @internal param Contracts $contract
     */
    public function getPolicyPrint(array $data): string
    {
        $contract = $data['contract'];
        $sample = filter_var(
            $data['sample'] ?? false,
            FILTER_VALIDATE_BOOL
        );

        return (new DriverService(app(PolicyPrinter::class)))->printPdf($contract, $sample);
    }

    /**
     * @param Contract $contract
     * @return array
     * @throws Exception
     */
    public function sendMail(Contract $contract): array
    {
        return (new DriverService(app(PolicyPrinter::class)))->sendMail($contract);
    }

    /**
     * @param Contract $contract
     * @return array|null
     * @throws DriverServiceException
     */
    public function getStatus(Contract $contract): array
    {
        return (new DriverService(app(PolicyPrinter::class)))->getStatus($contract);
    }
}
