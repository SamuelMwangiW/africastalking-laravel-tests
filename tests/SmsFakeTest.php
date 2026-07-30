<?php

declare(strict_types=1);

use SamuelMwangiW\Africastalking\Exceptions\AfricastalkingException;
use SamuelMwangiW\Africastalking\Facades\Africastalking;
use SamuelMwangiW\Africastalking\ValueObjects\SentMessageResponse;

beforeEach(fn() => Africastalking::fake());

it('sends a bulk sms with default fake responses', function (): void {
    $response = Africastalking::sms('Hello there')->to('+254700111222')->send();

    expect($response)->toBeInstanceOf(SentMessageResponse::class)
        ->and($response->recipients)->toHaveCount(1);

    Africastalking::fake()->assertSmsSentTo('+254700111222');
    Africastalking::fake()->assertSmsContains('Hello there');
    Africastalking::fake()->assertSmsCount(1);
    Africastalking::fake()->assertBulkSmsSent();
});

it('sends a premium sms', function (): void {
    Africastalking::sms('Premium hi')->to('+254700111222')->premium()->send();

    Africastalking::fake()->assertPremiumSmsSent();
});

it('asserts no sms was sent', function (): void {
    Africastalking::fake()->assertNoSmsSent();
});

it('makes every sms fail', function (): void {
    Africastalking::fake()->failSms();

    Africastalking::sms('Hello there')->to('+254700111222')->send();
})->throws(AfricastalkingException::class);

it('makes sms fail only for specific numbers', function (): void {
    Africastalking::fake()->failSmsTo(['+254700111222']);

    $response = Africastalking::sms('Hello there')
        ->to(['+254700111222', '+254700333444'])
        ->send();

    $statuses = $response->recipients->keyBy(fn($recipient) => $recipient->number->number)
        ->map(fn($recipient) => $recipient->status->value);

    expect($statuses->get('+254700111222'))->toBe('Failed')
        ->and($statuses->get('+254700333444'))->toBe('Success');
});
