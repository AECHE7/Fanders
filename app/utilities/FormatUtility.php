<?php

class FormatUtility
{
    public static function peso($amount): string
    {
        $num = is_numeric($amount) ? (float)$amount : 0.0;
        return '₱' . number_format($num, 2);
    }
}
