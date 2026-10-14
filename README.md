<p align="center">
  <a href="https://tezgafiskal.com"><img alt="Tezga" src="https://raw.githubusercontent.com/GoSimple-agency/.github/main/profile/assets/tezga-znak.svg" width="72"></a>
</p>

# rs-fiscal

[![CI](https://github.com/GoSimple-agency/rs-fiscal/actions/workflows/ci.yml/badge.svg)](https://github.com/GoSimple-agency/rs-fiscal/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/php-%E2%89%A58.1-777BB4)](https://www.php.net)

Small, dependency-free Serbian fiscal helpers for PHP: **PIB (tax ID) validation** and
**NBS IPS QR** code generation. Built from the public NBS and ISO standards, so it carries
nothing proprietary — use it in any project.

A [GoSimple](https://gosimple.space) open utility, from the team behind
[Tezga](https://tezgafiskal.com).

## Installation

```bash
composer require gosimple/rs-fiscal
```

Requires PHP 8.1+.

## PIB validation

A Serbian PIB is 9 digits whose last digit is an ISO 7064 MOD 11,10 check digit.

```php
use GoSimple\RsFiscal\Pib;

Pib::isValid('101134702');   // true
Pib::isValid('101134703');   // false (bad check digit)
Pib::checkDigit('10113470'); // 2
```

## NBS IPS QR

Builds the payload string for an [NBS IPS QR](https://ips.nbs.rs) code — the one a customer
scans in their banking app to pay instantly.

```php
use GoSimple\RsFiscal\IpsQr;

$payload = IpsQr::build([
    'account' => '310-1234567891211-86', // or a plain 18-digit account
    'name'    => 'ACME DOO',
    'amount'  => 3596.00,                 // optional
    'purpose' => 'Racun 2026-1042',       // optional
    'reference' => '97-1234',             // optional
]);
// K:PR|V:01|C:1|R:310123456789121186|N:ACME DOO|I:RSD3596,00|S:Racun 2026-1042|RO:97-1234

// Render $payload into an actual QR image with any QR library.
```

The amount tag always carries the mandatory decimal comma (`RSD3596,00`), the account is
normalized to the 18-digit IPS form, and the pipe character is stripped from free-text
fields. Validate the final payload against the official
[NBS generator/validator](https://ips.nbs.rs) before going to production.

> Note: this builds the IPS QR **string**. If you issue fiscal receipts through Tezga, each
> receipt already carries its IPS QR and the Tax Administration verification QR — see
> [tezga-sdk-php](https://github.com/GoSimple-agency/tezga-sdk-php).

## About

A product of **GoSimple**. See [`LICENSE`](./LICENSE) and [`NOTICE`](./NOTICE).
