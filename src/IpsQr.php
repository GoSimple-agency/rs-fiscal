<?php

declare(strict_types=1);

namespace GoSimple\RsFiscal;

/**
 * Builds an NBS IPS QR payload string, e.g.
 *   K:PR|V:01|C:1|R:840000000095584510|N:ACME DOO|I:RSD3596,00
 *
 * Fields follow the National Bank of Serbia IPS QR specification: tag and value joined by
 * a colon, fields separated by a pipe, no leading or trailing pipe, UTF-8. Validate the
 * result against the NBS generator/validator before going live: https://ips.nbs.rs
 */
final class IpsQr
{
    /**
     * @param array{
     *   account: string,
     *   name: string,
     *   amount?: float|int|string,
     *   currency?: string,
     *   payer?: string,
     *   paymentCode?: string,
     *   purpose?: string,
     *   reference?: string
     * } $p
     */
    public static function build(array $p): string
    {
        if (empty($p['account'])) {
            throw new \InvalidArgumentException('account is required.');
        }
        if (empty($p['name'])) {
            throw new \InvalidArgumentException('name is required.');
        }

        // Mandatory tags: K (recipient), V (version), C (UTF-8), R (account), N (name).
        $fields = [
            'K' => 'PR',
            'V' => '01',
            'C' => '1',
            'R' => self::normalizeAccount((string) $p['account']),
            'N' => self::clean((string) $p['name']),
        ];

        if (isset($p['amount']) && $p['amount'] !== '') {
            $fields['I'] = self::amount($p['amount'], (string) ($p['currency'] ?? 'RSD'));
        }
        if (!empty($p['payer'])) {
            $fields['P'] = self::clean((string) $p['payer']);
        }
        if (!empty($p['paymentCode'])) {
            $fields['SF'] = (string) $p['paymentCode'];
        }
        if (!empty($p['purpose'])) {
            $fields['S'] = self::clean((string) $p['purpose']);
        }
        if (!empty($p['reference'])) {
            $fields['RO'] = (string) $p['reference'];
        }

        $parts = [];
        foreach ($fields as $tag => $val) {
            $parts[] = $tag . ':' . $val;
        }

        return implode('|', $parts);
    }

    /**
     * Normalize a Serbian account to the 18-digit IPS form (bank 3 + account 13 + control 2).
     * Accepts "310-1234567891211-86" or a plain 18-digit string.
     */
    public static function normalizeAccount(string $account): string
    {
        $account = trim($account);
        if (preg_match('/^\d{18}$/', $account) === 1) {
            return $account;
        }
        if (preg_match('/^(\d{3})-(\d{1,13})-(\d{2})$/', $account, $m) === 1) {
            return $m[1] . str_pad($m[2], 13, '0', STR_PAD_LEFT) . $m[3];
        }

        throw new \InvalidArgumentException(
            'Account must be 18 digits or the bank-account-control form, e.g. 310-1234567891211-86.'
        );
    }

    /**
     * Format the amount tag: currency + integer part + "," + two decimals.
     * The decimal comma is mandatory in IPS; the max amount is 999999999999,99.
     */
    public static function amount(float|int|string $amount, string $currency = 'RSD'): string
    {
        $value = is_string($amount) ? (float) str_replace(',', '.', $amount) : (float) $amount;
        if ($value < 0) {
            throw new \InvalidArgumentException('amount must be >= 0.');
        }
        if ($value > 999999999999.99) {
            throw new \InvalidArgumentException('amount exceeds the IPS maximum (999999999999,99).');
        }

        return strtoupper($currency) . number_format($value, 2, ',', '');
    }

    /** NBS forbids the pipe inside a value; newlines are collapsed to spaces. */
    private static function clean(string $v): string
    {
        return trim(str_replace(['|', "\r", "\n"], ' ', $v));
    }
}
