<?php
namespace App\Core;

class View
{
    public static function e($str): string
    {
        return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
    }

    public static function money($n, int $decimals = 2): string
    {
        return number_format((float)$n, $decimals, '.', ',');
    }
}