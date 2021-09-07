<?php

namespace App\Services\PayService;

use App\Drivers\DriverResults\PayLinkInterface;

class PayLinkResult implements PayLinkInterface
{
    protected string $orderId;
    protected string $url;

    public function __construct(string $orderId, string $url)
    {
        $this->orderId = $orderId;
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getOrderId(): string
    {
        return $this->orderId;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }


    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'orderId' => $this->orderId,
        ];
    }

    public function getInvoiceNum(): string
    {
        return '';
    }
}
