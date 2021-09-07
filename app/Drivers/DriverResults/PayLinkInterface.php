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
    public function getOrderId(): string;
    public function getUrl(): string;
    public function getInvoiceNum(): string;
}
