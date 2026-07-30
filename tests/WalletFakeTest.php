<?php

declare(strict_types=1);

use SamuelMwangiW\Africastalking\Facades\Africastalking;
use SamuelMwangiW\Africastalking\ValueObjects\Balance;

beforeEach(fn() => Africastalking::fake());

it('returns a default fake wallet balance', function (): void {
    $balance = Africastalking::wallet()->balance();

    expect($balance)->toBeInstanceOf(Balance::class);
});

it('seeds the wallet balance response', function (): void {
    Africastalking::fake()->withWalletBalance('5000.50');

    $balance = Africastalking::wallet()->balance();

    expect($balance->amount)->toBe(5000.50)
        ->and($balance->currency->value)->toBe('KES');
});

it('seeds the wallet balance response with an explicit currency', function (): void {
    Africastalking::fake()->withWalletBalance('USD 12.34');

    $balance = Africastalking::wallet()->balance();

    expect($balance->amount)->toBe(12.34)
        ->and($balance->currency->value)->toBe('USD');
});

it('seeds the wallet balance response via fakeWalletBalance', function (): void {
    Africastalking::fake()->fakeWalletBalance(7500.25, 'UGX');

    $balance = Africastalking::wallet()->balance();

    expect($balance->amount)->toBe(7500.25)
        ->and($balance->currency->value)->toBe('UGX');
});
