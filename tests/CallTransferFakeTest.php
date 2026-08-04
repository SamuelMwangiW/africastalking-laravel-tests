<?php

declare(strict_types=1);

use SamuelMwangiW\Africastalking\Enum\CallLeg;
use SamuelMwangiW\Africastalking\Facades\Africastalking;
use SamuelMwangiW\Africastalking\ValueObjects\CallTransferResponse;

beforeEach(fn() => Africastalking::fake());

it('transfers a call with default fake responses', function (): void {
    $response = Africastalking::voice()
        ->transferCall('ATVId_47ef478e918923e7b2d0921ebd5b66a6')
        ->to('+254700111222')
        ->send();

    expect($response)->toBeInstanceOf(CallTransferResponse::class)
        ->and($response->isSuccessful())->toBeTrue();

    Africastalking::fake()->assertCallTransferCount(1);
    Africastalking::assertCallTransferCount(1);
    Africastalking::assertCallTransferredTo('+254700111222');
});

it('asserts no call was transferred', function (): void {
    Africastalking::fake()->assertNoCallTransferred();
});

it('asserts a call was transferred to a specific number', function (): void {
    Africastalking::voice()
        ->transferCall('ATVId_47ef478e918923e7b2d0921ebd5b66a6')
        ->to('+254700111222')
        ->send();

    Africastalking::fake()->assertCallTransferredTo('+254700111222');
});

it('asserts a call was transferred with a specific session id', function (): void {
    Africastalking::voice()
        ->transferCall('ATVId_47ef478e918923e7b2d0921ebd5b66a6')
        ->to('+254700111222')
        ->send();

    Africastalking::fake()->assertCallTransferredWithSessionId('ATVId_47ef478e918923e7b2d0921ebd5b66a6');
});

it('asserts a call was transferred with a specific leg', function (): void {
    Africastalking::voice()
        ->transferCall('ATVId_47ef478e918923e7b2d0921ebd5b66a6')
        ->to('+254700111222')
        ->callLeg(CallLeg::CALLER)
        ->send();

    Africastalking::fake()->assertCallTransferredWithLeg('caller');
});

it('asserts a transfer matched a callback', function (): void {
    Africastalking::voice()
        ->transferCall('ATVId_47ef478e918923e7b2d0921ebd5b66a6')
        ->to('+254700111222')
        ->send();

    Africastalking::fake()->assertCallTransferred(
        fn(array $body) => '+254700111222' === $body['phoneNumber'],
    );
});

it('fails a specific call transfer while succeeding others', function (): void {
    Africastalking::fake()->failCallTransfersTo(['+254700111222']);

    $failed = Africastalking::voice()->transferCall('ATVId_1')->to('+254700111222')->send();
    $succeeded = Africastalking::voice()->transferCall('ATVId_2')->to('+254700333444')->send();

    expect($failed->isSuccessful())->toBeFalse()
        ->and($succeeded->isSuccessful())->toBeTrue();
});

it('fails every call transfer', function (): void {
    Africastalking::fake()->failCallTransfers();

    $response = Africastalking::voice()->transferCall('ATVId_1')->to('+254700111222')->send();

    expect($response->isSuccessful())->toBeFalse();
});

it('reverts a prior failure with succeedCallTransfers', function (): void {
    Africastalking::fake()
        ->failCallTransfers()
        ->succeedCallTransfers();

    $response = Africastalking::voice()->transferCall('ATVId_1')->to('+254700111222')->send();

    expect($response->isSuccessful())->toBeTrue();
});
