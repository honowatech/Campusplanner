<?php

use Modules\Planning\Entities\Planning;
use Modules\Planning\Entities\ShiftPlanning;

if (! function_exists('truncate_text')) {
    /**
     * Truncates a string to a given length, adding an ellipsis if it exceeds
     * that length.
     *
     * @param  string  $text  The string to truncate.
     * @param  int  $length  The maximum length of the output string.
     * @return string The truncated string.
     */
    function truncate_text(?string $text, int $length = 35)
    {
        return mb_substr($text, 0, $length).'...';
    }
}

if (! function_exists('new_planning')) {
    /**
     * Creates a new instance of Planning.
     *
     * @return Planning
     */
    function new_planning()
    {
        return Modules\Planning\Facades\Planning::newPlanning();
    }
}

if (! function_exists('new_shift_planning')) {
    /**
     * Creates a new instance of ShiftPlanning.
     *
     * @return ShiftPlanning
     */
    function new_shift_planning()
    {
        return Modules\Planning\Facades\Planning::newShiftPlanning();
    }
}
