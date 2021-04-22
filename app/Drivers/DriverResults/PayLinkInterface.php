<?php

namespace App\Drivers\DriverResults;

use Illuminate\Contracts\Support\Arrayable;

interface PayLinkInterface extends Arrayable
{
    public function getOrderId(): string;
    public function getUrl(): string;
}
