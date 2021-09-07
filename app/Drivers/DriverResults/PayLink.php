<?php

namespace App\Drivers\DriverResults;

use Illuminate\Support\Arr;

/**
 * Class PayLink
 *
 * @package App\Drivers\DriverResults
 */
class PayLink implements PayLinkInterface
{
    protected $data = [];

    public function __construct(string $orderId, string $url, string $invoiceNum)
    {
        $this->data = compact(['orderId', 'url', 'invoiceNum']);
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    public function getOrderId(): string
    {
        return Arr::get($this->data, 'orderId', '');
    }

    public function getUrl(): string
    {
        return Arr::get($this->data, 'url', '');
    }

    public function getInvoiceNum(): string
    {
        return Arr::get($this->data, 'invoiceNum', '');
    }
}
