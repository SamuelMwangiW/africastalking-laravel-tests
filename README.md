# africastalking-laravel-tests

[![Latest Version on Packagist](https://img.shields.io/packagist/v/samuelmwangiw/africastalking-laravel-tests.svg?style=flat-square)](https://packagist.org/packages/samuelmwangiw/africastalking-laravel-tests)
[![run-tests](https://github.com/SamuelMwangiW/africastalking-laravel-tests/actions/workflows/run-tests.yml/badge.svg)](https://github.com/SamuelMwangiW/africastalking-laravel-tests/actions/workflows/run-tests.yml)
[![PHPStan](https://github.com/SamuelMwangiW/africastalking-laravel-tests/actions/workflows/phpstan.yml/badge.svg)](https://github.com/SamuelMwangiW/africastalking-laravel-tests/actions/workflows/phpstan.yml)
[![Code styling](https://github.com/SamuelMwangiW/africastalking-laravel-tests/actions/workflows/php-cs-fixer.yml/badge.svg)](https://github.com/SamuelMwangiW/africastalking-laravel-tests/actions/workflows/php-cs-fixer.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/samuelmwangiw/africastalking-laravel-tests.svg?style=flat-square)](https://packagist.org/packages/samuelmwangiw/africastalking-laravel-tests)

Testing helpers for [`samuelmwangiw/africastalking-laravel`](https://github.com/samuelmwangiw/africastalking-laravel). Fake the Africa's Talking API in your test suite and make expressive assertions against Voice, SMS, Airtime, Mobile Data, SIM Swap, Payment, Stash, Wallet and Application calls — no real HTTP requests, no sandbox credentials required.

## Installation

You can install the package via composer, as a dev dependency:

```bash
composer require samuelmwangiw/africastalking-laravel-tests --dev
```

The package auto-registers its service provider via Laravel package discovery.

## Usage

Call `Africastalking::fake()` at the start of a test to intercept every request the main package would otherwise send to Africa's Talking. Every domain (voice, sms, airtime, data, simswap, stash, wallet, application, payment) is pre-wired with realistic default responses, so your code under test can run unmodified.

```php
use SamuelMwangiW\Africastalking\Facades\Africastalking;

it('sends a welcome sms', function () {
    Africastalking::fake();

    Africastalking::sms('Welcome!')->to('+254700000000')->send();

    Africastalking::fake()->assertSmsSentTo('+254700000000');
});
```

`Africastalking::fake()` always returns the same instance within a test, so you can chain configuration and assertions across multiple calls.

## Global

| Method | Description |
|---|---|
| `Africastalking::fake(): FakeAfricastalking` | Base entry point; intercepts every Saloon request across all domains |
| `assertNothingSent(): void` | Asserts nothing was sent to Africa's Talking, across any service |
| `assertSentCount(int $count, ?string $service = null)` | Generic count assertion, optionally scoped to one service (`voice`, `sms`, `airtime`, `data`, `simswap`, `stash`, `wallet`, `application`, `payment`) |
| `recorded(string $service): Collection` | Escape hatch returning the raw recorded `Saloon\Http\Response` objects for a service |

## Voice

| Method | Description |
|---|---|
| `fake()->failVoiceCalls()` | Makes every subsequent voice call fail |
| `fake()->failVoiceCallsFor(array $phoneNumbers)` | Makes voice calls fail only for the given phone numbers |
| `assertVoiceCallCount(int $count)` | Asserts exactly N voice calls were placed |
| `assertNoVoiceCallsPlaced()` | Asserts no voice call was placed |
| `assertVoiceCallPlaced(?Closure $callback = null)` | Asserts a call was placed, optionally matching a closure over the request body |
| `assertVoiceCallPlacedTo(string $phoneNumber)` | Asserts a call was placed to a specific number |
| `assertVoiceCallPlacedFrom(string $callerId)` | Asserts a call was placed from a specific caller ID |
| `assertVoiceCallHadClientRequestId(string $id)` | Asserts a placed call carried the given `clientRequestId` |
| `assertVoiceCallHadActions(array $actionTypes)` | Asserts a placed call's `callActions` included the given action types, in order |
| `assertVoiceCallSaid(string $message)` | Asserts a placed call included a `say` action with the given exact message |
| `assertVoiceCallPlayed(string $url)` | Asserts a placed call included a `play` action with the given URL |
| `assertVoiceCallDialed(array $phoneNumbers)` | Asserts a placed call included a `dial` action to the given numbers |
| `assertVoiceCallQueued(string $queueName)` | Asserts a placed call included an `enqueue` action for the given queue |
| `fake()->withQueueStatus(array $entries)` | Seeds `queueStatus()` responses globally |
| `fake()->withQueueStatusFor(string $phoneNumber, array $entries)` | Seeds `queueStatus()` response for a specific phone number |
| `assertQueueStatusChecked(?string $phoneNumber = null)` | Asserts `queueStatus()` was called, optionally for a specific number |

## SMS

| Method | Description |
|---|---|
| `fake()->failSms()` | Makes every subsequent SMS send fail |
| `fake()->failSmsTo(array $phoneNumbers)` | Makes SMS sends fail only for the given phone numbers |
| `assertSmsSentTo(string $phoneNumber)` | Asserts an SMS was sent to a specific number |
| `assertSmsContains(string $text)` | Asserts a sent SMS's body contains the given text |
| `assertSmsCount(int $count)` | Asserts exactly N SMS messages were sent |
| `assertNoSmsSent()` | Asserts no SMS was sent |
| `assertBulkSmsSent()` | Asserts a bulk-mode SMS was sent |
| `assertPremiumSmsSent()` | Asserts a premium-mode SMS was sent |

## Airtime

| Method | Description |
|---|---|
| `fake()->failAirtime()` | Makes every subsequent airtime disbursement fail |
| `fake()->failAirtimeFor(array $phoneNumbers)` | Makes airtime disbursement fail only for the given phone numbers |
| `assertAirtimeSentTo(string $phoneNumber, ?string $amount = null)` | Asserts airtime was sent to a number, optionally checking the amount |
| `assertAirtimeCount(int $count)` | Asserts exactly N airtime disbursements were sent |
| `assertNoAirtimeSent()` | Asserts no airtime was sent |

## Mobile Data

| Method | Description |
|---|---|
| `fake()->failDataBundles()` | Makes every subsequent data bundle send fail |
| `assertDataBundleSentTo(string $phoneNumber, string $productName)` | Asserts a data bundle was sent to a number for a given product |

## SIM Swap

| Method | Description |
|---|---|
| `fake()->withSimSwapResult(string $phoneNumber, array $result)` | Seeds/overrides the SIM swap check result for a phone number |
| `assertSimSwapChecked(string $phoneNumber)` | Asserts a SIM swap check was performed for a number |

## Payment (mobile checkout)

| Method | Description |
|---|---|
| `fake()->failPayments()` | Makes every subsequent payment fail |
| `assertPaymentSent(?Closure $callback = null)` | Asserts a mobile checkout payment was sent |

## Stash

| Method | Description |
|---|---|
| `fake()->failStashTopup()` | Makes every subsequent Stash top-up fail |
| `assertStashToppedUp(?Closure $callback = null)` | Asserts a Stash top-up was sent |

## Wallet

| Method | Description |
|---|---|
| `fake()->withWalletBalance(string $amount)` | Seeds the wallet balance response |

## Application

| Method | Description |
|---|---|
| `fake()->withApplicationBalance(string $amount)` | Seeds the application balance response |

## Notes on failures

Calling one of the `fail*()` helpers does not throw an HTTP exception by itself — it makes the *mocked* Africa's Talking response describe a failure the same way the real API would (an empty `Recipients` list, a recipient marked `Failed`, a top-up `status` of `Failed`, and so on). Whether that turns into a thrown exception in your code under test depends entirely on `samuelmwangiw/africastalking-laravel`'s own domain logic — for example, `Message::send()` throws `AfricastalkingException` when no recipient was accepted, while `Airtime::send()` simply returns an `AirtimeResponse` with `numSent === 0` for the caller to inspect.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Samuel Mwangi](https://github.com/SamuelMwangiW)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
