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
        if (count($works) === 0) {
            return 'Официально не трудоустроен';
        }
        $work = $works[0];
        if (mb_stripos($work, 'индивидуальный предприниматель') !== false || mb_strtolower($work) === 'ип') {
            return 'Собственник бизнеса, Индивидуальный предприниматель';
        }

        return 'Работник по найму';
    }
}
