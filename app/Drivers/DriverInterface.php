<?php

namespace App\Drivers;

use App\Drivers\DriverResults\CalculatedInterface;
use App\Drivers\DriverResults\CreatedPolicyInterface;
use App\Drivers\DriverResults\PayLinkInterface;
use App\Models\Contract;
use App\Services\PayService\PayLinks;

/**
 * Interface DriverInterface
 *
 * @package App\Drivers
 */
interface DriverInterface
{
    /**
     * Подсчет стоимости и премии полиса
     *
     * @param  array  $data  Данные для расчета стоимости полиса
     *
     * @return CalculatedInterface
     */
    public function calculate(array $data): CalculatedInterface;

    /**
     * Получить ссылку на оплату
     *
     * @param  Contract  $contract
     * @param  PayLinks  $payLinks
     *
     * @return PayLinkInterface
     */
    public function getPayLink(Contract $contract, PayLinks $payLinks): PayLinkInterface;

    /**
     * Стартовая функция создания полиса
     *
     * @param  array  $data  Данные для создания договора по полису
     *
     * @return CreatedPolicyInterface
     */
    public function createPolicy(Contract $contract, array $data): CreatedPolicyInterface;

    /**
     * Функция вызываемая после оплаты полиса
     *
     * @param  Contract  $contract
     */
    public function payAccept(Contract $contract): void;

    /**
     * Отправка полиса на почту
     *
     * @param  Contract  $contract
     *
     * @return string Сообщение
     */
    public function sendPolice(Contract $contract): string;

    /**
     * @param  Contract  $contract
     *
     * @return array
     */
    public function getStatus(Contract $contract): array;

    /**
     * @return string
     */
    public static function code(): string;
}
