<?php

namespace App\Drivers\DriverResults;

class PayLink implements PayLinkInterface
{
    protected $data = [];

    public function __construct(string $orderId, string $url, float $invoiceNum)
    {
        $this->data = compact(['orderId', 'url', 'invoiceNum']);
    }

    public function toArray()
    {
        return $this->data;
    }

    public function getOrderId(): string
    {
        return \Arr::get($this->data, 'orderId', '');
    }

    public function getUrl(): string
    {
        return \Arr::get($this->data, 'url', '');
    }

    public function getInvoiceNum(): float
    {
        return \Arr::get($this->data, 'invoiceNum', 0);
    }
}