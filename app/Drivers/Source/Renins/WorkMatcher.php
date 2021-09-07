<?php

namespace App\Drivers\Source\Renins;

/**
 * Class WorkMatcher
 * @package App\Drivers\Source\Renins
 */
class WorkMatcher
{
    /**
     * @param array $works
     * @return string
     */
    public static function match(array $works): string
    {
        $result = 'Работник по найму';
        if (count($works) === 0) {
            $result = 'Официально не трудоустроен';
        }
        $work = $works[0];
        if (mb_stripos($work, 'индивидуальный предприниматель') !== false || mb_strtolower($work) === 'ип') {
            $result = 'Собственник бизнеса, Индивидуальный предприниматель';
        }

        return $result;
    }
}
