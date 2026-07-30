<?php

declare(strict_types=1);

use SamuelMwangiW\Africastalking\Facades\Africastalking;
use SamuelMwangiW\Africastalking\ValueObjects\MobileCheckoutResponse;

beforeEach(fn() => Africastalking::fake());

it('sends a mobile checkout payment with default fake responses', function (): void {
    $response = Africastalking::payment()->mobileCheckout()
        ->to('+254700111222')
        ->amount(100)
        ->product('TestProduct')
        ->send();

    expect($response)->toBeInstanceOf(MobileCheckoutResponse::class)
        ->and($response->status)->toBe('PendingConfirmation');

    Africastalking::fake()->assertPaymentSent();
    Africastalking::fake()->assertPaymentSent(fn(array $body) => '+254700111222' === $body['phoneNumber']);
});

it('makes every payment fail', function (): void {
    Africastalking::fake()->failPayments();

    $response = Africastalking::payment()->mobileCheckout()
        ->to('+254700111222')
        ->amount(100)
        ->product('TestProduct')
        ->send();

    expect($response->status)->toBe('Failed');
});
