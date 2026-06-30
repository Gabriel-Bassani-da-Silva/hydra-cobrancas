<?php

namespace App\Helpers;

/**
 * FormatHelper — Funções de formatação reutilizáveis nas views e componentes PHP.
 *
 * Uso nas views Blade:
 *   \App\Helpers\FormatHelper::phone($numero)
 *   \App\Helpers\FormatHelper::document($doc)
 *   \App\Helpers\FormatHelper::currency($valor)
 *   \App\Helpers\FormatHelper::date($data)
 */
class FormatHelper
{
    /**
     * Formata um número de telefone brasileiro (10 ou 11 dígitos).
     */
    public static function phone(string $num): string
    {
        $num = preg_replace('/\D/', '', $num);
        if (strlen($num) === 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $num);
        }
        if (strlen($num) === 10) {
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $num);
        }
        return $num;
    }

    /**
     * Formata CPF (11 dígitos) ou CNPJ (14 dígitos).
     */
    public static function document(string $doc): string
    {
        if (!$doc) return '';
        $doc = preg_replace('/\D/', '', $doc);
        if (strlen($doc) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $doc);
        }
        if (strlen($doc) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $doc);
        }
        return $doc;
    }

    /**
     * Formata um valor numérico como moeda BRL.
     * Ex: 1234.5 → "R$ 1.234,50"
     */
    public static function currency(float $val): string
    {
        return 'R$ ' . number_format($val, 2, ',', '.');
    }

    /**
     * Formata uma data no padrão dd/mm/yyyy.
     * Aceita "yyyy-mm-dd" ou "yyyy-mm-dd HH:ii:ss".
     */
    public static function date(string $date): string
    {
        if (!$date || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
            return '—';
        }
        return date('d/m/Y', strtotime($date));
    }

    /**
     * Formata data e hora.
     * Ex: "2024-06-29 14:30:00" → "29/06/2024 14:30"
     */
    public static function dateTime(string $date): string
    {
        if (!$date || $date === '0000-00-00 00:00:00') {
            return '—';
        }
        return date('d/m/Y H:i', strtotime($date));
    }
}
