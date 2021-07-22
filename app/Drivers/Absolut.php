<?

namespace App\Drivers;

use App\Drivers\DriverResults\CalculatedInterface;
use App\Drivers\DriverResults\CreatedPolicyInterface;
use App\Drivers\DriverResults\PayLinkInterface;
use App\Models\Contracts;
use App\Services\PayService\PayLinks;

class Absolut implements DriverInterface
{
    /**
     * @inheritDoc
     */
    public function calculate(array $data): CalculatedInterface
    {
        // TODO: Implement calculate() method.
    }

    /**
     * @inheritDoc
     */
    public function getPayLink(Contracts $contract, PayLinks $payLinks): PayLinkInterface
    {
        // TODO: Implement getPayLink() method.
    }

    /**
     * @inheritDoc
     */
    public function createPolicy(Contracts $contract, array $data): CreatedPolicyInterface
    {
        // TODO: Implement createPolicy() method.
    }

    /**
     * @inheritDoc
     */
    public function printPolicy(
        Contracts $contract,
        bool $sample,
        bool $reset,
        ?string $filePath = null
    ) {
        // TODO: Implement printPolicy() method.
    }

    /**
     * @inheritDoc
     */
    public function payAccept(Contracts $contract): void
    {
        // TODO: Implement payAccept() method.
    }

    /**
     * @inheritDoc
     */
    public function sendPolice(Contracts $contract): string
    {
        // TODO: Implement sendPolice() method.
    }

    /**
     * @inheritDoc
     */
    public function getStatus(Contracts $contract): array
    {
        // TODO: Implement getStatus() method.
    }
}