<?php


namespace App\Drivers\DriverResults;


class PayLink implements PayLinkInterface
{

    protected ?int $orderId;
    protected ?int $url;
    protected ?float $invoiceNum;

    /**
     * Calculated constructor.
     * @param int|null $orderId
     * @param string|null $url
     * @param float|null $invoiceNum
     */
    public function __construct(?int $orderId, ?string $url = null, ?float $invoiceNum = null)
    {
        $this->orderId    = $orderId;
        $this->url        = $url;
        $this->invoiceNum = $invoiceNum;
    }

    public function toArray()
    {
        return [
            'orderId'    => $this->orderId,
            'formUrl'    => $this->url,
            'invoiceNum' => $this->invoiceNum,
        ];
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getInvoiceNum(): float
    {
        return $this->invoiceNum;
    }
}
