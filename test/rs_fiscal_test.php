<?php

declare(strict_types=1);

// Run: php -d error_reporting=-1 test/rs_fiscal_test.php
require __DIR__ . '/../src/Pib.php';
require __DIR__ . '/../src/IpsQr.php';

use GoSimple\RsFiscal\IpsQr;
use GoSimple\RsFiscal\Pib;

$fail = 0;
function ok(string $name, bool $cond): void
{
    global $fail;
    if ($cond) { echo "OK  {$name}\n"; } else { $fail++; echo "FAIL {$name}\n"; }
}
function eq(string $name, mixed $a, mixed $b): void
{
    global $fail;
    if ($a === $b) { echo "OK  {$name}\n"; } else { $fail++; echo "FAIL {$name}\n  got: " . var_export($a, true) . "\n  exp: " . var_export($b, true) . "\n"; }
}

// --- PIB (verified against python-stdnum test vectors) ---
ok('PIB 101134702 valid', Pib::isValid('101134702'));
ok('PIB 101134703 invalid', !Pib::isValid('101134703'));
eq('PIB check digit of 10113470', Pib::checkDigit('10113470'), 2);
ok('PIB tolerates whitespace', Pib::isValid(' 101134702 '));
ok('PIB rejects 8 digits', !Pib::isValid('10113470'));
ok('PIB rejects letters', !Pib::isValid('10113470A'));

// --- IPS QR ---
eq(
    'IPS QR full string',
    IpsQr::build(['account' => '840000000095584510', 'name' => 'UPRAVA CARINA', 'amount' => 216436.50]),
    'K:PR|V:01|C:1|R:840000000095584510|N:UPRAVA CARINA|I:RSD216436,50'
);
eq('IPS account normalize dashed', IpsQr::normalizeAccount('310-1234567891211-86'), '310123456789121186');
eq('IPS account short middle padded', IpsQr::normalizeAccount('265-1234-11'), '265000000000123411');
eq('IPS amount whole keeps comma', IpsQr::amount(3596), 'RSD3596,00');
eq('IPS amount from string', IpsQr::amount('1025.1'), 'RSD1025,10');
ok('IPS pipe stripped from name', !str_contains(IpsQr::build(['account' => '840000000095584510', 'name' => 'A|B']), 'N:A|B'));

// optional tags appear only when set
$withRef = IpsQr::build(['account' => '840000000095584510', 'name' => 'ACME', 'reference' => '97-1234', 'purpose' => 'Racun 1']);
ok('IPS includes RO when set', str_contains($withRef, '|RO:97-1234'));
ok('IPS includes S when set', str_contains($withRef, '|S:Racun 1'));
ok('IPS omits amount when unset', !str_contains($withRef, '|I:'));

// guards
try { IpsQr::normalizeAccount('123'); ok('IPS bad account throws', false); } catch (\InvalidArgumentException) { ok('IPS bad account throws', true); }
try { IpsQr::build(['account' => '840000000095584510']); ok('IPS missing name throws', false); } catch (\InvalidArgumentException) { ok('IPS missing name throws', true); }

echo $fail === 0 ? "\nAll passed\n" : "\n{$fail} failed\n";
exit($fail === 0 ? 0 : 1);
