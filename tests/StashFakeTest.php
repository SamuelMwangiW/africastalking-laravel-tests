<?php

declare(strict_types=1);

use SamuelMwangiW\Africastalking\Facades\Africastalking;
use SamuelMwangiW\Africastalking\ValueObjects\StashTopupResponse;

beforeEach(fn() => Africastalking::fake());

it('tops up stash with default fake responses', function (): void {
    $response = Africastalking::stash()->product('TestProduct')->send('KES', 100);

    expect($response)->toBeInstanceOf(StashTopupResponse::class)
        ->and($response->status->value)->toBe('Success');

    Africastalking::fake()->assertStashToppedUp();
    Africastalking::fake()->assertStashToppedUp(fn(array $body) => 100 === $body['amount']);
});

it('makes every stash top-up fail', function (): void {
    Africastalking::fake()->failStashTopup();

    $response = Africastalking::stash()->product('TestProduct')->send('KES', 100);

    expect($response->status->value)->toBe('Failed');
});
