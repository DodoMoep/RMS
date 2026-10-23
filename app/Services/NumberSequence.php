<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Vergibt fortlaufende Nummern ohne Doppelvergabe.
 *
 * Der Zaehler steht in der Tabelle number_sequences und wird in einer Transaktion
 * mit Zeilensperre hochgezaehlt. Dadurch erhalten gleichzeitige Anfragen
 * unterschiedliche Nummern, und eine vergebene Nummer wird nie erneut vergeben,
 * auch wenn der zugehoerige Datensatz geloescht wird.
 */
class NumberSequence
{
    /**
     * Liefert den naechsten Wert des Zaehlers $name.
     *
     * @param  Closure(): int  $currentMax  Hoechster bereits vergebener Wert; wird nur beim
     *                                      ersten Zugriff auf einen Zaehler ausgewertet,
     *                                      damit bestehende Daten weitergezaehlt werden.
     */
    public static function next(string $name, Closure $currentMax): int
    {
        return DB::transaction(function () use ($name, $currentMax) {
            if (! DB::table('number_sequences')->where('name', $name)->exists()) {
                DB::table('number_sequences')->insertOrIgnore([
                    'name' => $name,
                    'last_value' => $currentMax(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $last = (int) DB::table('number_sequences')
                ->where('name', $name)
                ->lockForUpdate()
                ->value('last_value');

            $next = $last + 1;

            DB::table('number_sequences')
                ->where('name', $name)
                ->update(['last_value' => $next, 'updated_at' => now()]);

            return $next;
        });
    }

    /**
     * Ermittelt den hoechsten numerischen Suffix einer Nummer wie "ORD-2026-0042"
     * (Praefix "ORD-2026-") in einer Tabelle, inklusive weich geloeschter Zeilen.
     * Tabellen- und Spaltenname muessen feste Werte aus dem Code sein.
     */
    public static function highestSuffix(string $table, string $column, string $prefix): int
    {
        if (! preg_match('/^[a-z_]+$/', $table.$column)) {
            throw new \InvalidArgumentException('Ungueltiger Tabellen- oder Spaltenname.');
        }

        // Laenge zuerst, damit "10000" nach "9999" einsortiert wird.
        $value = DB::table($table)
            ->where($column, 'like', $prefix.'%')
            ->orderByRaw("LENGTH({$column}) desc")
            ->orderByDesc($column)
            ->value($column);

        return $value === null ? 0 : (int) substr($value, strlen($prefix));
    }
}
