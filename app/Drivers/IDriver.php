<?php

namespace App\Drivers;

use App\Models\Contracts;
use App\Services\DriverService;
use Illuminate\Http\Request;
use Strahovka\Payment\PayService;

interface IDriver
{
    /**
     * Подсчет стоимости и премии полиса
     * @see BaseDriver::collectData()
     * @see DriverService::calculate()
     * @param array $data
     * @return mixed
     */
    public function calculate(array $data): array;

    /**
     * Получить ссылку на оплату
     * @param PayService $service
     * @param Contracts $contract
     * @return mixed
     */
    public function getPayLink(PayService $service, Contracts $contract, Request $request);

    /**
     * Стартовая функция создания полиса
     * @see BaseDriver::createPolicy()
     * @param Request $data
     * @return array
     */
    public function createPolicy(Request $data): array;

    /**
     * Функция печати полиса
     * @param Contracts $contract
     * @param bool $sample
     * @param bool $reset
     * @param string|null $filePath
     * @return mixed
     */
    public function printPolicy(Contracts $contract, bool $sample, bool $reset, ?string $filePath = null): string;

    /**
     * Функция вызываемая при оплате полиса
     * @param Contracts $contract
     */
    public function statusConfirmed(Contracts $contract): void;

    /**
     * Функция вызываемая при попытке получить ссылку на оплату
     * @param Contracts $contract
     */
    public function triggerGetLink(Contracts $contract): void;
}
