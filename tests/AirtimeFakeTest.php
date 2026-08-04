<?php

declare(strict_types=1);

use SamuelMwangiW\Africastalking\Facades\Africastalking;
use SamuelMwangiW\Africastalking\ValueObjects\AirtimeResponse;

beforeEach(fn() => Africastalking::fake());

it('sends airtime with default fake responses', function (): void {
    $response = Africastalking::airtime()->to('+254700111222', 'KES', 100)->send();

    expect($response)->toBeInstanceOf(AirtimeResponse::class)
        ->and($response->numSent)->toBe(1);

    Africastalking::fake()->assertAirtimeSentTo('+254700111222');
    Africastalking::fake()->assertAirtimeSentTo('+254700111222', 'KES 100');
    Africastalking::fake()->assertSentAirtime('+254700111222', 100);
    Africastalking::fake()->assertAirtimeCount(1);

    Africastalking::assertSentAirtime('+254700111222', 100);
});

it('asserts no airtime was sent', function (): void {
    Africastalking::fake()->assertNoAirtimeSent();
    Africastalking::fake()->assertAirtimeNotSent();
});

it('asserts airtime was sent with an idempotency key', function (): void {
    Africastalking::airtime()
        ->idempotent('idem-key-123')
        ->to('+254700111222', 'KES', 100)
        ->send();

    Africastalking::fake()->assertSentAirtimeIdempotently('idem-key-123');
});

it('counts every recipient across multiple recipients in one send', function (): void {
    Africastalking::airtime()
        ->to('+254700111222', 'KES', 100)
        ->add('+254700333444', 'KES', 200)
        ->send();

    Africastalking::fake()->assertAirtimeCount(2);
});

it('makes every airtime disbursement fail', function (): void {
    Africastalking::fake()->failAirtime();

    $response = Africastalking::airtime()->to('+254700111222', 'KES', 100)->send();

    expect($response->numSent)->toBe(0);
});

it('makes airtime fail only for specific numbers', function (): void {
    Africastalking::fake()->failAirtimeFor(['+254700111222']);

    $response = Africastalking::airtime()
        ->to('+254700111222', 'KES', 100)
        ->add('+254700333444', 'KES', 100)
        ->send();

    $statuses = $response->responses->keyBy(fn($recipient) => $recipient->phoneNumber->number)
        ->map(fn($recipient) => $recipient->status->value);

    expect($statuses->get('+254700111222'))->toBe('Failed')
        ->and($statuses->get('+254700333444'))->toBe('Sent');
});
