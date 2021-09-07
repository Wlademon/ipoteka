<?php

namespace App\Drivers;

use App\Models\Contract;
use Closure;

/**
 * Interface LocalPrintDriverInterface
 *
 * @package App\Drivers
 */
interface OutPrintDriverInterface
{
    /**
     * Функция печати полиса
     *
     * @param  Contract  $contract
     * @param  bool      $sample  Шаблон
     * @param  bool      $reset   Перепечатать
     *
     * @return string|array Файл в формате base64
     */
    public function printPolicy(
        Contract $contract,
        bool $sample,
        bool $reset
    ): array;

    /**
     * Получить идентификаторы полисов
     *
     * @param  Contract  $contract
     *
     * @return array
     */
    public function getPoliceIds(Contract $contract): array;
}
