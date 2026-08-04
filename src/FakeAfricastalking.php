<?php

declare(strict_types=1);

namespace SamuelMwangiW\Africastalking\Testing;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PHPUnit\Framework\Assert as PHPUnit;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;
use Saloon\Laravel\Facades\Saloon;
use SamuelMwangiW\Africastalking\Saloon\Requests\Airtime\SendRequest as AirtimeSendRequest;
use SamuelMwangiW\Africastalking\Saloon\Requests\Application\BalanceRequest;
use SamuelMwangiW\Africastalking\Saloon\Requests\Messaging\BulkSmsRequest;
use SamuelMwangiW\Africastalking\Saloon\Requests\Messaging\PremiumSmsRequest;
use SamuelMwangiW\Africastalking\Saloon\Requests\MobileData\SendRequest as MobileDataSendRequest;
use SamuelMwangiW\Africastalking\Saloon\Requests\Payment\MobileCheckoutRequest;
use SamuelMwangiW\Africastalking\Saloon\Requests\Payment\StashTopupRequest;
use SamuelMwangiW\Africastalking\Saloon\Requests\Payment\WalletBalanceRequest;
use SamuelMwangiW\Africastalking\Saloon\Requests\SimSwap\SendRequest as SimSwapSendRequest;
use SamuelMwangiW\Africastalking\Saloon\Requests\Voice\CallRequest;
use SamuelMwangiW\Africastalking\Saloon\Requests\Voice\CallTransferRequest;
use SamuelMwangiW\Africastalking\Saloon\Requests\Voice\CapabilityTokenRequest;
use SamuelMwangiW\Africastalking\Saloon\Requests\Voice\QueueStatusRequest;

/**
 * The object returned by Africastalking::fake(). Intercepts every Saloon
 * request the main package sends (voice, sms, airtime, data, simswap,
 * stash, wallet, application, payment) with realistic default responses,
 * and exposes fluent helpers to seed or fail specific responses plus
 * assertions to inspect what was actually sent.
 */
class FakeAfricastalking
{
    private const array SERVICE_REQUESTS = [
        'voice' => [CallRequest::class, QueueStatusRequest::class, CallTransferRequest::class],
        'sms' => [BulkSmsRequest::class, PremiumSmsRequest::class],
        'airtime' => [AirtimeSendRequest::class],
        'data' => [MobileDataSendRequest::class],
        'simswap' => [SimSwapSendRequest::class],
        'stash' => [StashTopupRequest::class],
        'wallet' => [WalletBalanceRequest::class],
        'application' => [BalanceRequest::class],
        'payment' => [MobileCheckoutRequest::class],
    ];

    private readonly MockClient $mockClient;

    private bool $allVoiceCallsFail = false;

    /** @var array<int,string> */
    private array $failingVoiceNumbers = [];

    /** @var array<int,array> */
    private array $queueStatusDefaultEntries = [];

    /** @var array<string,array> */
    private array $queueStatusEntriesByNumber = [];

    private bool $allCallTransfersFail = false;

    /** @var array<int,string> */
    private array $failingCallTransferNumbers = [];

    private bool $allSmsFail = false;

    /** @var array<int,string> */
    private array $failingSmsNumbers = [];

    private bool $allAirtimeFail = false;

    /** @var array<int,string> */
    private array $failingAirtimeNumbers = [];

    private bool $allDataBundlesFail = false;

    /** @var array<string,array> */
    private array $simSwapResultsByNumber = [];

    private bool $allPaymentsFail = false;

    private bool $allStashFails = false;

    private ?string $walletBalance = null;

    private ?string $applicationBalance = null;

    private function __construct()
    {
        MockClient::destroyGlobal();

        $this->mockClient = Saloon::fake([]);

        $this->registerDefaults();
    }

    public static function setUp(): self
    {
        return new self();
    }

    // ------------------------------------------------------------------
    // Global
    // ------------------------------------------------------------------

    public function assertNothingDispatched(): void
    {
        $this->mockClient->assertNothingSent();
    }

    public function assertSentCount(int $count, ?string $service = null): void
    {
        if (null === $service) {
            $this->mockClient->assertSentCount($count);

            return;
        }

        $actual = $this->countOf(...$this->requestsForService($service));

        PHPUnit::assertSame(
            $count,
            $actual,
            "Expected {$count} request(s) for the [{$service}] service, but {$actual} were recorded.",
        );
    }

    /**
     * @return Collection<int,Response>
     */
    public function recorded(string $service): Collection
    {
        return $this->requestsOf(...$this->requestsForService($service));
    }

    // ------------------------------------------------------------------
    // Voice
    // ------------------------------------------------------------------

    public function failVoiceCalls(): static
    {
        $this->allVoiceCallsFail = true;

        return $this;
    }

    public function failVoiceCallsFor(array $phoneNumbers): static
    {
        array_push($this->failingVoiceNumbers, ...array_map(self::normalizePhone(...), $phoneNumbers));

        return $this;
    }

    public function succeedVoiceCalls(): static
    {
        $this->allVoiceCallsFail = false;
        $this->failingVoiceNumbers = [];

        return $this;
    }

    public function withQueueStatus(array $entries): static
    {
        $this->queueStatusDefaultEntries = $entries;

        return $this;
    }

    public function withQueueStatusFor(string $phoneNumber, array $entries): static
    {
        $this->queueStatusEntriesByNumber[self::normalizePhone($phoneNumber)] = $entries;

        return $this;
    }

    public function assertVoiceCallCount(int $count): void
    {
        PHPUnit::assertSame($count, $this->countOf(CallRequest::class));
    }

    public function assertNoVoiceCallsPlaced(): void
    {
        $this->assertVoiceCallCount(0);
    }

    public function assertNothingCalled(): void
    {
        $this->assertVoiceCallCount(0);
    }

    public function assertVoiceCallPlaced(?Closure $callback = null): void
    {
        $bodies = $this->bodiesOf(CallRequest::class);

        PHPUnit::assertTrue($bodies->isNotEmpty(), 'No voice call was placed.');

        if (null !== $callback) {
            PHPUnit::assertTrue(
                $bodies->contains(static fn(array $body) => false !== $callback($body)),
                'No voice call matched the given callback.',
            );
        }
    }

    public function assertVoiceCallPlacedTo(string $phoneNumber): void
    {
        $matched = $this->bodiesOf(CallRequest::class)->contains(
            fn(array $body) => $this->numberIn($phoneNumber, array_map(self::normalizePhone(...), (array) Arr::get($body, 'to', []))),
        );

        PHPUnit::assertTrue($matched, "No voice call was placed to [{$phoneNumber}].");
    }

    public function assertCallMadeTo(string $phone): void
    {
        $this->assertVoiceCallPlacedTo($phone);
    }

    public function assertVoiceCallPlacedFrom(string $callerId): void
    {
        $matched = $this->bodiesOf(CallRequest::class)->contains(
            static fn(array $body) => self::normalizePhone((string) Arr::get($body, 'from', '')) === self::normalizePhone($callerId),
        );

        PHPUnit::assertTrue($matched, "No voice call was placed from [{$callerId}].");
    }

    public function assertCallMadeFrom(string $phone): void
    {
        $this->assertVoiceCallPlacedFrom($phone);
    }

    public function assertVoiceCallHadClientRequestId(string $id): void
    {
        $matched = $this->bodiesOf(CallRequest::class)->contains(
            static fn(array $body) => Arr::get($body, 'clientRequestId') === $id,
        );

        PHPUnit::assertTrue($matched, "No voice call had the client request id [{$id}].");
    }

    /**
     * Voice calls do not carry an Idempotency-Key (the main package's
     * VoiceCall does not use HasIdempotency); this asserts the closest
     * equivalent the SDK exposes, the call's clientRequestId.
     */
    public function assertCallRequestId(string $id): void
    {
        $this->assertVoiceCallHadClientRequestId($id);
    }

    public function assertVoiceCallHadActions(array $actionTypes): void
    {
        $matched = $this->bodiesOf(CallRequest::class)->contains(
            fn(array $body) => self::isOrderedSubsequence($actionTypes, Arr::pluck($this->callActionsOf($body), 'actionType')),
        );

        PHPUnit::assertTrue($matched, 'No voice call included the expected call actions ['.implode(', ', $actionTypes).'] in order.');
    }

    public function assertVoiceCallSaid(string $message): void
    {
        $matched = $this->bodiesOf(CallRequest::class)->contains(
            fn(array $body) => collect($this->callActionsOfType($body, 'Say'))
                ->contains(static fn(array $action) => Arr::get($action, 'text') === $message),
        );

        PHPUnit::assertTrue($matched, "No voice call said [{$message}].");
    }

    public function assertVoiceCallPlayed(string $url): void
    {
        $matched = $this->bodiesOf(CallRequest::class)->contains(
            fn(array $body) => collect($this->callActionsOfType($body, 'Play'))
                ->contains(static fn(array $action) => Arr::get($action, 'url') === $url),
        );

        PHPUnit::assertTrue($matched, "No voice call played [{$url}].");
    }

    public function assertVoiceCallDialed(array $phoneNumbers): void
    {
        $expected = array_map(self::normalizePhone(...), $phoneNumbers);
        sort($expected);

        $matched = $this->bodiesOf(CallRequest::class)->contains(function (array $body) use ($expected) {
            return collect($this->callActionsOfType($body, 'Dial'))->contains(function (array $action) use ($expected) {
                $actual = array_map(self::normalizePhone(...), (array) Arr::get($action, 'phoneNumbers', []));
                sort($actual);

                return $actual === $expected;
            });
        });

        PHPUnit::assertTrue($matched, 'No voice call dialed the expected phone numbers.');
    }

    public function assertVoiceCallQueued(string $queueName): void
    {
        $matched = $this->bodiesOf(CallRequest::class)->contains(
            fn(array $body) => collect($this->callActionsOfType($body, 'Enqueue'))
                ->contains(static fn(array $action) => Arr::get($action, 'name') === $queueName),
        );

        PHPUnit::assertTrue($matched, "No voice call enqueued [{$queueName}].");
    }

    public function assertQueueStatusChecked(?string $phoneNumber = null): void
    {
        $bodies = $this->bodiesOf(QueueStatusRequest::class);

        PHPUnit::assertTrue($bodies->isNotEmpty(), 'The queue status was never checked.');

        if (null !== $phoneNumber) {
            $matched = $bodies->contains(
                fn(array $body) => $this->numberIn($phoneNumber, self::phoneListFrom(Arr::get($body, 'phoneNumbers', ''))),
            );

            PHPUnit::assertTrue($matched, "The queue status for [{$phoneNumber}] was never checked.");
        }
    }

    public function failCallTransfers(): static
    {
        $this->allCallTransfersFail = true;

        return $this;
    }

    public function failCallTransfersTo(array $phoneNumbers): static
    {
        array_push($this->failingCallTransferNumbers, ...array_map(self::normalizePhone(...), $phoneNumbers));

        return $this;
    }

    public function succeedCallTransfers(): static
    {
        $this->allCallTransfersFail = false;
        $this->failingCallTransferNumbers = [];

        return $this;
    }

    public function assertCallTransferCount(int $count): void
    {
        PHPUnit::assertSame($count, $this->countOf(CallTransferRequest::class));
    }

    public function assertNoCallTransferred(): void
    {
        $this->assertCallTransferCount(0);
    }

    public function assertCallTransferred(?Closure $callback = null): void
    {
        $bodies = $this->bodiesOf(CallTransferRequest::class);

        PHPUnit::assertTrue($bodies->isNotEmpty(), 'No call was transferred.');

        if (null !== $callback) {
            PHPUnit::assertTrue(
                $bodies->contains(static fn(array $body) => false !== $callback($body)),
                'No transferred call matched the given callback.',
            );
        }
    }

    public function assertCallTransferredTo(string $phoneNumber): void
    {
        $matched = $this->bodiesOf(CallTransferRequest::class)->contains(
            fn(array $body) => self::normalizePhone((string) Arr::get($body, 'phoneNumber', '')) === self::normalizePhone($phoneNumber),
        );

        PHPUnit::assertTrue($matched, "No call was transferred to [{$phoneNumber}].");
    }

    public function assertCallTransferredWithSessionId(string $sessionId): void
    {
        $matched = $this->bodiesOf(CallTransferRequest::class)->contains(
            static fn(array $body) => Arr::get($body, 'sessionId') === $sessionId,
        );

        PHPUnit::assertTrue($matched, "No call with session id [{$sessionId}] was transferred.");
    }

    public function assertCallTransferredWithLeg(string $callLeg): void
    {
        $matched = $this->bodiesOf(CallTransferRequest::class)->contains(
            static fn(array $body) => Arr::get($body, 'callLeg') === $callLeg,
        );

        PHPUnit::assertTrue($matched, "No call was transferred with the [{$callLeg}] leg.");
    }

    // ------------------------------------------------------------------
    // SMS
    // ------------------------------------------------------------------

    public function failSms(): static
    {
        $this->allSmsFail = true;

        return $this;
    }

    public function failSmsTo(array $phoneNumbers): static
    {
        array_push($this->failingSmsNumbers, ...array_map(self::normalizePhone(...), $phoneNumbers));

        return $this;
    }

    public function assertSmsSentTo(string $phoneNumber): void
    {
        $matched = $this->bodiesOf(BulkSmsRequest::class, PremiumSmsRequest::class)->contains(
            fn(array $body) => $this->numberIn($phoneNumber, self::phoneListFrom(Arr::get($body, 'to', ''))),
        );

        PHPUnit::assertTrue($matched, "No SMS was sent to [{$phoneNumber}].");
    }

    public function assertSmsSentFrom(string $sender): void
    {
        $matched = $this->bodiesOf(BulkSmsRequest::class, PremiumSmsRequest::class)->contains(
            static fn(array $body) => Arr::get($body, 'from') === $sender,
        );

        PHPUnit::assertTrue($matched, "No SMS was sent from [{$sender}].");
    }

    public function assertSmsContains(string $text): void
    {
        $matched = $this->bodiesOf(BulkSmsRequest::class, PremiumSmsRequest::class)->contains(
            static fn(array $body) => str_contains((string) Arr::get($body, 'message', ''), $text),
        );

        PHPUnit::assertTrue($matched, "No SMS contained [{$text}].");
    }

    public function assertSmsCount(int $count): void
    {
        PHPUnit::assertSame($count, $this->countOf(BulkSmsRequest::class, PremiumSmsRequest::class));
    }

    public function assertNoSmsSent(): void
    {
        $this->assertSmsCount(0);
    }

    /**
     * Scoped to SMS. For the cross-service assertion, see assertNothingDispatched().
     */
    public function assertNothingSent(): void
    {
        $this->assertSmsCount(0);
    }

    public function assertBulkSmsSent(): void
    {
        PHPUnit::assertTrue($this->requestsOf(BulkSmsRequest::class)->isNotEmpty(), 'No bulk SMS was sent.');
    }

    public function assertPremiumSmsSent(): void
    {
        PHPUnit::assertTrue($this->requestsOf(PremiumSmsRequest::class)->isNotEmpty(), 'No premium SMS was sent.');
    }

    // ------------------------------------------------------------------
    // Airtime
    // ------------------------------------------------------------------

    public function failAirtime(): static
    {
        $this->allAirtimeFail = true;

        return $this;
    }

    public function failAirtimeFor(array $phoneNumbers): static
    {
        array_push($this->failingAirtimeNumbers, ...array_map(self::normalizePhone(...), $phoneNumbers));

        return $this;
    }

    public function assertAirtimeSentTo(string $phoneNumber, ?string $amount = null): void
    {
        $matched = $this->bodiesOf(AirtimeSendRequest::class)->contains(
            fn(array $body) => collect((array) Arr::get($body, 'recipients', []))->contains(function (array $recipient) use ($phoneNumber, $amount) {
                if (self::normalizePhone((string) Arr::get($recipient, 'phoneNumber', '')) !== self::normalizePhone($phoneNumber)) {
                    return false;
                }

                return null === $amount || str_contains((string) Arr::get($recipient, 'amount', ''), $amount);
            }),
        );

        PHPUnit::assertTrue($matched, "No airtime was sent to [{$phoneNumber}].");
    }

    /**
     * Like assertAirtimeSentTo(), but requires an exact whole-unit amount match.
     */
    public function assertSentAirtime(string $phone, int $amount): void
    {
        $matched = $this->bodiesOf(AirtimeSendRequest::class)->contains(
            fn(array $body) => collect((array) Arr::get($body, 'recipients', []))->contains(function (array $recipient) use ($phone, $amount) {
                if (self::normalizePhone((string) Arr::get($recipient, 'phoneNumber', '')) !== self::normalizePhone($phone)) {
                    return false;
                }

                return $amount === self::amountValue((string) Arr::get($recipient, 'amount', ''));
            }),
        );

        PHPUnit::assertTrue($matched, "No airtime of [{$amount}] was sent to [{$phone}].");
    }

    public function assertAirtimeCount(int $count): void
    {
        $actual = $this->bodiesOf(AirtimeSendRequest::class)->sum(
            static fn(array $body) => count((array) Arr::get($body, 'recipients', [])),
        );

        PHPUnit::assertSame($count, $actual, "Expected {$count} airtime disbursement(s), but {$actual} were recorded.");
    }

    public function assertNoAirtimeSent(): void
    {
        $this->assertAirtimeCount(0);
    }

    public function assertAirtimeNotSent(): void
    {
        $this->assertAirtimeCount(0);
    }

    public function assertSentAirtimeIdempotently(string $key): void
    {
        $matched = $this->headersOf(AirtimeSendRequest::class)->contains(
            static fn(array $headers) => Arr::get($headers, 'Idempotency-Key') === $key,
        );

        PHPUnit::assertTrue($matched, "No airtime request was sent with the idempotency key [{$key}].");
    }

    // ------------------------------------------------------------------
    // Mobile Data
    // ------------------------------------------------------------------

    public function failDataBundles(): static
    {
        $this->allDataBundlesFail = true;

        return $this;
    }

    public function assertDataBundleSentTo(string $phoneNumber, string $productName): void
    {
        $matched = $this->bodiesOf(MobileDataSendRequest::class)->contains(function (array $body) use ($phoneNumber, $productName) {
            if (Arr::get($body, 'productName') !== $productName) {
                return false;
            }

            return collect((array) Arr::get($body, 'recipients', []))->contains(
                fn(array $recipient) => self::normalizePhone((string) Arr::get($recipient, 'phoneNumber', '')) === self::normalizePhone($phoneNumber),
            );
        });

        PHPUnit::assertTrue($matched, "No data bundle was sent to [{$phoneNumber}] for product [{$productName}].");
    }

    // ------------------------------------------------------------------
    // SIM Swap
    // ------------------------------------------------------------------

    public function withSimSwapResult(string $phoneNumber, array $result): static
    {
        $this->simSwapResultsByNumber[self::normalizePhone($phoneNumber)] = $result;

        return $this;
    }

    public function assertSimSwapChecked(string $phoneNumber): void
    {
        $matched = $this->bodiesOf(SimSwapSendRequest::class)->contains(
            fn(array $body) => $this->numberIn($phoneNumber, array_map(self::normalizePhone(...), (array) Arr::get($body, 'phoneNumbers', []))),
        );

        PHPUnit::assertTrue($matched, "No SIM swap check was performed for [{$phoneNumber}].");
    }

    // ------------------------------------------------------------------
    // Payment (mobile checkout)
    // ------------------------------------------------------------------

    public function failPayments(): static
    {
        $this->allPaymentsFail = true;

        return $this;
    }

    public function assertPaymentSent(?Closure $callback = null): void
    {
        $bodies = $this->bodiesOf(MobileCheckoutRequest::class);

        PHPUnit::assertTrue($bodies->isNotEmpty(), 'No payment was sent.');

        if (null !== $callback) {
            PHPUnit::assertTrue(
                $bodies->contains(static fn(array $body) => false !== $callback($body)),
                'No payment matched the given callback.',
            );
        }
    }

    // ------------------------------------------------------------------
    // Stash
    // ------------------------------------------------------------------

    public function failStashTopup(): static
    {
        $this->allStashFails = true;

        return $this;
    }

    public function assertStashToppedUp(?Closure $callback = null): void
    {
        $bodies = $this->bodiesOf(StashTopupRequest::class);

        PHPUnit::assertTrue($bodies->isNotEmpty(), 'No Stash top-up was sent.');

        if (null !== $callback) {
            PHPUnit::assertTrue(
                $bodies->contains(static fn(array $body) => false !== $callback($body)),
                'No Stash top-up matched the given callback.',
            );
        }
    }

    // ------------------------------------------------------------------
    // Wallet
    // ------------------------------------------------------------------

    public function withWalletBalance(string $amount): static
    {
        $this->walletBalance = self::withCurrencyPrefix($amount);

        return $this;
    }

    public function fakeWalletBalance(float $balance, string $currency = 'KES'): static
    {
        $this->walletBalance = "{$currency} {$balance}";

        return $this;
    }

    // ------------------------------------------------------------------
    // Application
    // ------------------------------------------------------------------

    public function withApplicationBalance(string $amount): static
    {
        $this->applicationBalance = self::withCurrencyPrefix($amount);

        return $this;
    }

    private static function normalizePhone(string $phone): string
    {
        return str_replace([' ', '-', '.'], '', $phone);
    }

    /**
     * @return array<int,string>
     */
    private static function phoneListFrom(mixed $value): array
    {
        if (is_array($value)) {
            return array_map(self::normalizePhone(...), $value);
        }

        if (blank($value)) {
            return [];
        }

        return array_map(self::normalizePhone(...), explode(',', (string) $value));
    }

    private static function withCurrencyPrefix(string $amount): string
    {
        return preg_match('/^[A-Z]{3}\s/', $amount) ? $amount : "KES {$amount}";
    }

    private static function amountValue(string $amount): int
    {
        return (int) preg_replace('/[^0-9.]/', '', $amount);
    }

    private static function isOrderedSubsequence(array $needle, array $haystack): bool
    {
        $i = 0;

        foreach ($haystack as $item) {
            if ($i < count($needle) && $item === $needle[$i]) {
                $i++;
            }
        }

        return $i === count($needle);
    }

    // ------------------------------------------------------------------
    // Default response builders
    // ------------------------------------------------------------------

    private function registerDefaults(): void
    {
        $this->mockClient->addResponses([
            AirtimeSendRequest::class => fn(PendingRequest $pendingRequest) => $this->airtimeResponse($pendingRequest),
            BalanceRequest::class => fn() => $this->applicationBalanceResponse(),
            BulkSmsRequest::class => fn(PendingRequest $pendingRequest) => $this->smsResponse($pendingRequest),
            PremiumSmsRequest::class => fn(PendingRequest $pendingRequest) => $this->smsResponse($pendingRequest),
            MobileDataSendRequest::class => fn(PendingRequest $pendingRequest) => $this->mobileDataResponse($pendingRequest),
            MobileCheckoutRequest::class => fn() => $this->paymentResponse(),
            StashTopupRequest::class => fn() => $this->stashResponse(),
            WalletBalanceRequest::class => fn() => $this->walletBalanceResponse(),
            SimSwapSendRequest::class => fn(PendingRequest $pendingRequest) => $this->simSwapResponse($pendingRequest),
            CallRequest::class => fn(PendingRequest $pendingRequest) => $this->voiceCallResponse($pendingRequest),
            QueueStatusRequest::class => fn(PendingRequest $pendingRequest) => $this->queueStatusResponse($pendingRequest),
            CallTransferRequest::class => fn(PendingRequest $pendingRequest) => $this->callTransferResponse($pendingRequest),
            CapabilityTokenRequest::class => fn(PendingRequest $pendingRequest) => $this->capabilityTokenResponse($pendingRequest),
        ]);
    }

    private function voiceCallResponse(PendingRequest $pendingRequest): MockResponse
    {
        $body = $pendingRequest->body()?->all() ?? [];
        $recipients = (array) Arr::get($body, 'to', []);

        $entries = array_map(function (string $number) {
            $failed = $this->allVoiceCallsFail || $this->numberIn($number, $this->failingVoiceNumbers);

            return [
                'phoneNumber' => $number,
                'status' => $failed ? 'InvalidPhoneNumber' : 'Queued',
                'sessionId' => $failed ? '' : 'ATVId_'.Str::random(32),
            ];
        }, $recipients);

        return MockResponse::make([
            'entries' => $entries,
            'errorMessage' => $this->allVoiceCallsFail ? 'InsufficientBalance' : 'None',
        ], 200);
    }

    private function queueStatusResponse(PendingRequest $pendingRequest): MockResponse
    {
        $body = $pendingRequest->body()?->all() ?? [];
        $numbers = self::phoneListFrom(Arr::get($body, 'phoneNumbers', ''));

        foreach ($numbers as $number) {
            if (array_key_exists($number, $this->queueStatusEntriesByNumber)) {
                return MockResponse::make([
                    'entries' => $this->queueStatusEntriesByNumber[$number],
                    'errorMessage' => 'None',
                    'status' => 'Success',
                ]);
            }
        }

        return MockResponse::make([
            'entries' => $this->queueStatusDefaultEntries,
            'errorMessage' => 'None',
            'status' => 'Success',
        ]);
    }

    private function callTransferResponse(PendingRequest $pendingRequest): MockResponse
    {
        $body = $pendingRequest->body()?->all() ?? [];
        $number = (string) Arr::get($body, 'phoneNumber', '');

        $failed = $this->allCallTransfersFail || $this->numberIn($number, $this->failingCallTransferNumbers);

        return MockResponse::make([
            'callTransferResponse' => [
                'status' => $failed ? 'Failed' : 'Success',
                'errorMessage' => $failed ? 'InvalidPhoneNumber' : 'None',
            ],
        ], 200);
    }

    private function capabilityTokenResponse(PendingRequest $pendingRequest): MockResponse
    {
        $body = $pendingRequest->body()?->all() ?? [];

        return MockResponse::make([
            'clientName' => Arr::get($body, 'clientName', 'Client'),
            'incoming' => true,
            'lifeTimeSec' => mb_rtrim((string) Arr::get($body, 'expire', '86400s'), 's'),
            'outgoing' => true,
            'token' => 'ATCAPtkn_'.Str::random(48),
        ], 201);
    }

    private function smsResponse(PendingRequest $pendingRequest): MockResponse
    {
        $body = $pendingRequest->body()?->all() ?? [];
        $numbers = self::phoneListFrom(Arr::get($body, 'to', ''));

        if ($this->allSmsFail || [] === $numbers) {
            return MockResponse::make([
                'SMSMessageData' => ['Message' => 'InvalidRequest', 'Recipients' => []],
            ], 201);
        }

        $recipients = array_map(function (string $number) {
            $failed = $this->numberIn($number, $this->failingSmsNumbers);

            return [
                'number' => $number,
                'cost' => $failed ? 'KES 0.0000' : 'KES 0.8000',
                'messageId' => $failed ? 'None' : 'ATXid_'.Str::random(32),
                'status' => $failed ? 'Failed' : 'Success',
                'statusCode' => $failed ? 401 : 101,
            ];
        }, $numbers);

        $sent = collect($recipients)->where('status', 'Success')->count();

        return MockResponse::make([
            'SMSMessageData' => [
                'Message' => "Sent to {$sent}/".count($numbers).' Total Cost: USD 0.0079 Message parts: 1',
                'Recipients' => $recipients,
            ],
        ], 201);
    }

    private function airtimeResponse(PendingRequest $pendingRequest): MockResponse
    {
        if ($this->allAirtimeFail) {
            return MockResponse::make([
                'errorMessage' => 'InsufficientBalance',
                'numSent' => 0,
                'responses' => [],
                'totalAmount' => 'USD 0.0000',
                'totalDiscount' => 'USD 0.0000',
            ], 201);
        }

        $body = $pendingRequest->body()?->all() ?? [];
        $recipients = (array) Arr::get($body, 'recipients', []);
        $numSent = 0;

        $responses = array_map(function (array $recipient) use (&$numSent) {
            $number = (string) Arr::get($recipient, 'phoneNumber', '');
            $failed = $this->numberIn($number, $this->failingAirtimeNumbers);

            if ( ! $failed) {
                $numSent++;
            }

            return [
                'phoneNumber' => $number,
                'amount' => (string) Arr::get($recipient, 'amount', 'KES 0.0000'),
                'discount' => $failed ? 'KES 0.0000' : 'KES 0.0400',
                'errorMessage' => $failed ? 'UserInBlacklist' : 'None',
                'requestId' => $failed ? 'None' : 'ATQid_'.Str::random(32),
                'status' => $failed ? 'Failed' : 'Sent',
            ];
        }, $recipients);

        return MockResponse::make([
            'errorMessage' => 'None',
            'numSent' => $numSent,
            'responses' => $responses,
            'totalAmount' => 'USD 0.0000',
            'totalDiscount' => 'USD 0.0000',
        ], 201);
    }

    private function mobileDataResponse(PendingRequest $pendingRequest): MockResponse
    {
        $body = $pendingRequest->body()?->all() ?? [];
        $recipients = (array) Arr::get($body, 'recipients', []);

        $entries = array_map(fn(array $recipient) => [
            'phoneNumber' => (string) Arr::get($recipient, 'phoneNumber', ''),
            'provider' => 'Athena',
            'status' => $this->allDataBundlesFail ? 'Failed' : 'Queued',
            'transactionId' => $this->allDataBundlesFail ? 'None' : 'ATPid_'.Str::random(32),
            'value' => 'KES 5.0000',
        ], $recipients);

        return MockResponse::make(['entries' => $entries], 201);
    }

    private function simSwapResponse(PendingRequest $pendingRequest): MockResponse
    {
        $body = $pendingRequest->body()?->all() ?? [];
        $numbers = (array) Arr::get($body, 'phoneNumbers', []);

        $responses = array_map(function (string $number) {
            $default = [
                'phoneNumber' => [
                    'number' => $number,
                    'carrierName' => 'Safaricom',
                    'countryCode' => 254,
                    'networkCode' => 'Safaricom',
                    'numberType' => 'Mobile',
                ],
                'status' => 'Processed',
                'cost' => ['amount' => 5, 'currencyCode' => 'KES'],
                'requestId' => 'ATXSwid_'.Str::random(32),
            ];

            $override = $this->simSwapResultsByNumber[self::normalizePhone($number)] ?? [];

            return array_merge($default, $override);
        }, $numbers);

        return MockResponse::make([
            'responses' => $responses,
            'status' => 'Processed',
            'totalCost' => ['amount' => 0.05, 'currencyCode' => 'USD'],
            'transactionId' => (string) Str::uuid(),
        ], 200);
    }

    private function paymentResponse(): MockResponse
    {
        if ($this->allPaymentsFail) {
            return MockResponse::make([
                'description' => 'The request could not be processed',
                'providerChannel' => '',
                'status' => 'Failed',
                'transactionId' => 'None',
            ], 201);
        }

        return MockResponse::make([
            'description' => 'Waiting for user input',
            'providerChannel' => '525900',
            'status' => 'PendingConfirmation',
            'transactionId' => 'ATPid_'.Str::random(32),
        ], 201);
    }

    private function stashResponse(): MockResponse
    {
        if ($this->allStashFails) {
            return MockResponse::make([
                'description' => 'Failed to top up stash',
                'status' => 'Failed',
                'transactionId' => 'None',
            ], 201);
        }

        return MockResponse::make([
            'description' => 'Topped up user stash.',
            'status' => 'Success',
            'transactionId' => 'ATPid_'.Str::random(32),
        ], 201);
    }

    private function walletBalanceResponse(): MockResponse
    {
        return MockResponse::make([
            'balance' => $this->walletBalance ?? 'KES 116085350.3080',
            'status' => 'Success',
        ], 200);
    }

    private function applicationBalanceResponse(): MockResponse
    {
        return MockResponse::make([
            'UserData' => ['balance' => $this->applicationBalance ?? 'USD 999999589.6168'],
        ], 200);
    }

    // ------------------------------------------------------------------
    // Introspection helpers
    // ------------------------------------------------------------------

    /**
     * @return array<int,class-string>
     */
    private function requestsForService(string $service): array
    {
        return self::SERVICE_REQUESTS[$service]
            ?? throw new InvalidArgumentException("Unknown Africa's Talking service [{$service}].");
    }

    /**
     * @return Collection<int,Response>
     */
    private function requestsOf(string ...$requestClasses): Collection
    {
        return collect($this->mockClient->getRecordedResponses())
            ->filter(static fn(Response $response) => in_array($response->getPendingRequest()->getRequest()::class, $requestClasses, true))
            ->values();
    }

    /**
     * @return Collection<int,array>
     */
    private function bodiesOf(string ...$requestClasses): Collection
    {
        return $this->requestsOf(...$requestClasses)
            ->map(static fn(Response $response) => $response->getPendingRequest()->body()?->all() ?? []);
    }

    private function countOf(string ...$requestClasses): int
    {
        return $this->requestsOf(...$requestClasses)->count();
    }

    /**
     * @return Collection<int,array<string,mixed>>
     */
    private function headersOf(string ...$requestClasses): Collection
    {
        return $this->requestsOf(...$requestClasses)
            ->map(static fn(Response $response) => $response->getPendingRequest()->headers()->all());
    }

    /**
     * @return array<int,array>
     */
    private function callActionsOf(array $body): array
    {
        return (array) Arr::get($body, 'callActions', []);
    }

    /**
     * @return array<int,array>
     */
    private function callActionsOfType(array $body, string $type): array
    {
        return array_values(array_filter(
            $this->callActionsOf($body),
            static fn(array $action) => Arr::get($action, 'actionType') === $type,
        ));
    }

    private function numberIn(string $number, array $normalizedList): bool
    {
        return in_array(self::normalizePhone($number), $normalizedList, true);
    }
}
