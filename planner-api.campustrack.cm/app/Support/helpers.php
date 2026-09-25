<?php

use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

if (! function_exists('hours_diff_expr')) {
    /**
     * Expression SQL portable (MySQL / SQLite) : durée en heures entre
     * ending_hour et starting_hour.
     */
    function hours_diff_expr(): Expression
    {
        $driver = DB::connection()->getDriverName();

        return in_array($driver, ['mysql', 'mariadb'])
            ? DB::raw('TIME_TO_SEC(TIMEDIFF(ending_hour, starting_hour)) / 3600')
            : DB::raw("(strftime('%s', ending_hour) - strftime('%s', starting_hour)) / 3600.0");
    }
}
