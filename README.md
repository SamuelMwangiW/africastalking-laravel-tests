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

Once `Africastalking::fake()` has been called, every assertion and read-only method below (`assert*`, `recorded()`) can also be called directly on the facade, without `->fake()`:

```php
Africastalking::fake();

Africastalking::sms('Welcome!')->to('+254700000000')->send();

Africastalking::assertSmsSentTo('+254700000000');
```

Seeding and failure-injection methods (`fail*`, `with*`, `fakeWalletBalance`) are only reachable via `Africastalking::fake()->...` — calling them directly on the facade, to keep it visually obvious where a test is changing fake behavior versus asserting on it, throws a `BadMethodCallException`. Calling any assertion directly before `Africastalking::fake()` has run does too.

## Global

| Method | Description |
|---|---|
| `Africastalking::fake(): FakeAfricastalking` | Base entry point; intercepts every Saloon request across all domains |
| `assertNothingDispatched(): void` | Asserts nothing was sent to Africa's Talking, across any service |
| `assertSentCount(int $count, ?string $service = null)` | Generic count assertion, optionally scoped to one service (`voice`, `sms`, `airtime`, `data`, `simswap`, `stash`, `wallet`, `application`, `payment`) |
| `recorded(string $service): Collection` | Escape hatch returning the raw recorded `Saloon\Http\Response` objects for a service |

## Voice

| Method | Description |
|---|---|
| `fake()->failVoiceCalls()` | Makes every subsequent voice call fail |
| `fake()->failVoiceCallsFor(array $phoneNumbers)` | Makes voice calls fail only for the given phone numbers |
| `fake()->succeedVoiceCalls()` | Reverts a prior `failVoiceCalls()`/`failVoiceCallsFor()`, making subsequent calls succeed again |
| `assertVoiceCallCount(int $count)` | Asserts exactly N voice calls were placed |
| `assertNoVoiceCallsPlaced()` / `assertNothingCalled()` | Asserts no voice call was placed |
| `assertVoiceCallPlaced(?Closure $callback = null)` | Asserts a call was placed, optionally matching a closure over the request body |
| `assertVoiceCallPlacedTo(string $phoneNumber)` / `assertCallMadeTo(string $phone)` | Asserts a call was placed to a specific number |
| `assertVoiceCallPlacedFrom(string $callerId)` / `assertCallMadeFrom(string $phone)` | Asserts a call was placed from a specific caller ID |
| `assertVoiceCallHadClientRequestId(string $id)` / `assertCallRequestId(string $id)` | Asserts a placed call carried the given `clientRequestId` (voice calls have no idempotency key in the main package — this is the closest equivalent) |
| `assertVoiceCallHadActions(array $actionTypes)` | Asserts a placed call's `callActions` included the given action types, in order |
| `assertVoiceCallSaid(string $message)` | Asserts a placed call included a `say` action with the given exact message |
| `assertVoiceCallPlayed(string $url)` | Asserts a placed call included a `play` action with the given URL |
| `assertVoiceCallDialed(array $phoneNumbers)` | Asserts a placed call included a `dial` action to the given numbers |
| `assertVoiceCallQueued(string $queueName)` | Asserts a placed call included an `enqueue` action for the given queue |
| `fake()->withQueueStatus(array $entries)` | Seeds `queueStatus()` responses globally |
| `fake()->withQueueStatusFor(string $phoneNumber, array $entries)` | Seeds `queueStatus()` response for a specific phone number |
| `assertQueueStatusChecked(?string $phoneNumber = null)` | Asserts `queueStatus()` was called, optionally for a specific number |
| `fake()->failCallTransfers()` | Makes every subsequent `transfer()`/`transferCall()` fail |
| `fake()->failCallTransfersTo(array $phoneNumbers)` | Makes call transfers fail only for the given destination phone numbers |
| `fake()->succeedCallTransfers()` | Reverts a prior `failCallTransfers()`/`failCallTransfersTo()`, making subsequent transfers succeed again |
| `assertCallTransferCount(int $count)` | Asserts exactly N calls were transferred |
| `assertNoCallTransferred()` | Asserts no call was transferred |
| `assertCallTransferred(?Closure $callback = null)` | Asserts a call was transferred, optionally matching a closure over the request body |
| `assertCallTransferredTo(string $phoneNumber)` | Asserts a call was transferred to a specific number |
| `assertCallTransferredWithSessionId(string $sessionId)` | Asserts a transfer was made for the given call session |
| `assertCallTransferredWithLeg(string $callLeg)` | Asserts a transfer specified the given call leg (`caller`/`callee`) |

## SMS

| Method | Description |
|---|---|
| `fake()->failSms()` | Makes every subsequent SMS send fail |
| `fake()->failSmsTo(array $phoneNumbers)` | Makes SMS sends fail only for the given phone numbers |
| `assertSmsSentTo(string $phoneNumber)` | Asserts an SMS was sent to a specific number |
| `assertSmsSentFrom(string $sender)` | Asserts the sender ID (`from`) was set correctly |
| `assertSmsContains(string $text)` | Asserts a sent SMS's body contains the given text |
| `assertSmsCount(int $count)` | Asserts exactly N SMS messages were sent |
| `assertNoSmsSent()` / `assertNothingSent()` | Asserts no SMS was sent. `assertNothingSent()` is scoped to SMS only — for the cross-service assertion, use the global `assertNothingDispatched()` |
| `assertBulkSmsSent()` | Asserts a bulk-mode SMS was sent |
| `assertPremiumSmsSent()` | Asserts a premium-mode SMS was sent |

## Airtime

| Method | Description |
|---|---|
| `fake()->failAirtime()` | Makes every subsequent airtime disbursement fail |
| `fake()->failAirtimeFor(array $phoneNumbers)` | Makes airtime disbursement fail only for the given phone numbers |
| `assertAirtimeSentTo(string $phoneNumber, ?string $amount = null)` | Asserts airtime was sent to a number, optionally checking the amount (substring match) |
| `assertSentAirtime(string $phone, int $amount)` | Asserts airtime was sent to a number for an exact whole-unit amount |
| `assertAirtimeCount(int $count)` | Asserts exactly N airtime disbursements were sent |
| `assertNoAirtimeSent()` / `assertAirtimeNotSent()` | Asserts no airtime was sent |
| `assertSentAirtimeIdempotently(string $key)` | Asserts an airtime request carried the given `Idempotency-Key` header |

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
| `fake()->fakeWalletBalance(float $balance, string $currency = 'KES')` | Same as `withWalletBalance()`, but takes the amount and currency as separate arguments |

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
