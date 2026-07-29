<?php

declare(strict_types=1);

use SamuelMwangiW\Africastalking\Enum\BundlesUnit;
use SamuelMwangiW\Africastalking\Enum\BundlesValidity;
use SamuelMwangiW\Africastalking\Facades\Africastalking;
use SamuelMwangiW\Africastalking\ValueObjects\DataBundlesResponse;

beforeEach(fn() => Africastalking::fake());

it('sends a data bundle with default fake responses', function (): void {
    $response = Africastalking::mobileData()
        ->productName('TestProduct')
        ->to('+254700111222', 100, BundlesValidity::MONTHLY, BundlesUnit::MB)
        ->send();

    expect($response)->toBeInstanceOf(DataBundlesResponse::class)
        ->and($response->entries)->toHaveCount(1)
        ->and($response->entries->first()->status->value)->toBe('Queued');

    Africastalking::fake()->assertDataBundleSentTo('+254700111222', 'TestProduct');
});

it('makes every data bundle send fail', function (): void {
    Africastalking::fake()->failDataBundles();

    $response = Africastalking::mobileData()
        ->productName('TestProduct')
        ->to('+254700111222', 100, BundlesValidity::MONTHLY, BundlesUnit::MB)
        ->send();

    expect($response->entries->first()->status->value)->toBe('Failed');
});
