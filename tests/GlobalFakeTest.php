<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use SamuelMwangiW\Africastalking\Facades\Africastalking;
use SamuelMwangiW\Africastalking\Testing\FakeAfricastalking;

it('returns a FakeAfricastalking instance and reuses it across calls', function (): void {
    $fake = Africastalking::fake();

    expect($fake)->toBeInstanceOf(FakeAfricastalking::class)
        ->and(Africastalking::fake())->toBe($fake);
});

it('asserts nothing was dispatched', function (): void {
    Africastalking::fake()->assertNothingDispatched();
});

it('fails assertNothingDispatched once a request has been sent', function (): void {
    Africastalking::fake();

    Africastalking::app()->balance();

    Africastalking::fake()->assertNothingDispatched();
})->throws(PHPUnit\Framework\ExpectationFailedException::class);

it('asserts a global sent count', function (): void {
    Africastalking::fake();

    Africastalking::app()->balance();
    Africastalking::wallet()->balance();

    Africastalking::fake()->assertSentCount(2);
});

it('asserts a sent count scoped to a service', function (): void {
    Africastalking::fake();

    Africastalking::app()->balance();
    Africastalking::wallet()->balance();
    Africastalking::wallet()->balance();

    Africastalking::fake()->assertSentCount(1, 'application');
    Africastalking::fake()->assertSentCount(2, 'wallet');
});

it('rejects an unknown service name', function (): void {
    Africastalking::fake()->assertSentCount(0, 'not-a-real-service');
})->throws(InvalidArgumentException::class);

it('returns the raw recorded responses for a service', function (): void {
    Africastalking::fake();

    Africastalking::app()->balance();

    $recorded = Africastalking::fake()->recorded('application');

    expect($recorded)->toBeInstanceOf(Collection::class)->toHaveCount(1);
});
