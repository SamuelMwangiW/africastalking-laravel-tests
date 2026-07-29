<?php

declare(strict_types=1);

use SamuelMwangiW\Africastalking\Facades\Africastalking;
use SamuelMwangiW\Africastalking\ValueObjects\Responses\InsightsResponse;

beforeEach(fn() => Africastalking::fake());

it('checks a sim swap with default fake responses', function (): void {
    $response = Africastalking::simSwap()->for('+254700111222')->send();

    expect($response)->toBeInstanceOf(InsightsResponse::class)
        ->and($response->items)->toHaveCount(1)
        ->and($response->items->first()->status->value)->toBe('Processed');

    Africastalking::fake()->assertSimSwapChecked('+254700111222');
});

it('seeds the sim swap result for a specific number', function (): void {
    Africastalking::fake()->withSimSwapResult('+254700111222', ['status' => 'Failed']);

    $response = Africastalking::simSwap()->for('+254700111222')->send();

    expect($response->items->first()->status->value)->toBe('Failed');
});
