<?php

namespace App\Drivers\DriverResults;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Interface PayLinkInterface
 *
 * @package App\Drivers\DriverResults
 */
interface PayLinkInterface extends Arrayable
{
    /**
     * @return string
     */
    public function getOrderId(): string;

    /**
     * @return string
     */
    public function getUrl(): string;

    /**
     * @return string
     */
    public function getInvoiceNum(): string;
}
