<?php

namespace App\Drivers;

use App\Drivers\DriverResults\CalculatedInterface;
use App\Drivers\DriverResults\CreatedPolicyInterface;
use App\Drivers\DriverResults\PayLinkInterface;
use App\Models\Contracts;
use App\Services\PayService\PayLinks;

/**
 * Interface DriverInterface
 * @package App\Drivers
 */
interface DriverInterface
{
    /**
     * Подсчет стоимости и премии полиса
     *
     * @param array $data Данные для расчета стоимости полиса
     * @return CalculatedInterface
     */
    public function calculate(array $data): CalculatedInterface;

    /**
     * Получить ссылку на оплату
     *
     * @param Contracts $contract
     * @param PayLinks $payLinks
     * @return PayLinkInterface
     */
    public function getPayLink(Contracts $contract, PayLinks $payLinks): PayLinkInterface;

    /**
     * Стартовая функция создания полиса
     *
     * @param array $data Данные для создания договора по полису
     * @return array
     */
    public function createPolicy(array $data): CreatedPolicyInterface;

    /**
     * Функция печати полиса
     *
     * @param Contracts $contract
     * @param bool $sample Шаблон
     * @param bool $reset Перепечатать
     * @param string|null $filePath Путь сохранения файла
     * @return mixed Файл в формате base64
     */
    public function printPolicy(Contracts $contract, bool $sample, bool $reset, ?string $filePath = null): string;

    /**
     * Функция вызываемая после оплаты полиса
     *
     * @param Contracts $contract
     */
    public function payAccept(Contracts $contract): void;

    /**
     * Отправка полиса на почту
     *
     * @param Contracts $contract
     * @return string Сообщение
     */
    public function sendPolice(Contracts $contract): string;

    /**
     * @param Contracts $contract
     * @return array
     */
    public function getStatus(Contracts $contract): array;

}
