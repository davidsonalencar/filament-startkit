<?php

namespace App\Support;

class FilamentDateFormats
{
    public static function date(): string
    {
        return match (app()->getLocale()) {
            'pt_BR', 'pt-BR', 'pt' => 'd/m/Y',
            'en', 'en_US' => 'm/d/Y',
            'en_GB' => 'd/m/Y',
            'es', 'es_ES' => 'd/m/Y',
            default => 'Y-m-d',
        };
    }

    public static function time(): string
    {
        return match (app()->getLocale()) {
            'pt_BR', 'pt-BR', 'pt' => 'H:i',
            'en', 'en_US' => 'g:i A',
            'en_GB' => 'H:i',
            'es', 'es_ES' => 'H:i',
            default => 'H:i',
        };
    }

    public static function dateTime(): string
    {
        $date = self::date();
        $time = self::time();
        return "$date $time";
    }
}
