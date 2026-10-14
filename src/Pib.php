<?php

declare(strict_types=1);

namespace GoSimple\RsFiscal;

/**
 * Serbian tax ID (PIB) validation.
 *
 * A PIB is 9 digits; the last digit is an ISO 7064 MOD 11,10 check digit over the first 8.
 */
final class Pib
{
    /** True if $pib is 9 digits with a valid check digit. */
    public static function isValid(string $pib): bool
    {
        $pib = trim($pib);
        if (preg_match('/^\d{9}$/', $pib) !== 1) {
            return false;
        }

        return self::checkDigit(substr($pib, 0, 8)) === (int) $pib[8];
    }

    /**
     * Check digit (0-9) for the first 8 digits, per ISO 7064 MOD 11,10.
     *
     * @throws \InvalidArgumentException if $first8 is not exactly 8 digits.
     */
    public static function checkDigit(string $first8): int
    {
        if (preg_match('/^\d{8}$/', $first8) !== 1) {
            throw new \InvalidArgumentException('Expected exactly 8 digits.');
        }

        $p = 10;
        for ($i = 0; $i < 8; $i++) {
            $s = ((int) $first8[$i] + $p) % 10;
            if ($s === 0) {
                $s = 10;
            }
            $p = (2 * $s) % 11;
        }

        return (11 - $p) % 10;
    }
}
