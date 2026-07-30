<?php

declare(strict_types=1);

use SamuelMwangiW\Africastalking\Facades\Africastalking;
use SamuelMwangiW\Africastalking\ValueObjects\Balance;

beforeEach(fn() => Africastalking::fake());

it('returns a default fake application balance', function (): void {
    $balance = Africastalking::app()->balance();

    expect($balance)->toBeInstanceOf(Balance::class);
});

it('seeds the application balance response', function (): void {
    Africastalking::fake()->withApplicationBalance('USD 42.00');

    $balance = Africastalking::app()->balance();

    expect($balance->amount)->toBe(42.0)
        ->and($balance->currency->value)->toBe('USD');
});
