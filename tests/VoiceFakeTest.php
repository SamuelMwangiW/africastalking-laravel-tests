<?php

declare(strict_types=1);

use SamuelMwangiW\Africastalking\Facades\Africastalking;
use SamuelMwangiW\Africastalking\ValueObjects\VoiceCallResponse;

beforeEach(fn() => Africastalking::fake());

it('places a voice call with default fake responses', function (): void {
    $response = Africastalking::voice()->call('+254700111222')->say('Hello there')->send();

    expect($response)->toBeInstanceOf(VoiceCallResponse::class)
        ->and($response->errorMessage)->toBe('None')
        ->and($response->recipients)->toHaveCount(1);

    Africastalking::fake()->assertVoiceCallCount(1);
});

it('asserts no voice calls were placed', function (): void {
    Africastalking::fake()->assertNoVoiceCallsPlaced();
});

it('asserts a call was placed to and from specific numbers', function (): void {
    Africastalking::voice()->call('+254700111222')->as('+254711000000')->requestId('req-123')->say('Hi')->send();

    Africastalking::fake()->assertVoiceCallPlacedTo('+254700111222');
    Africastalking::fake()->assertVoiceCallPlacedFrom('+254711000000');
    Africastalking::fake()->assertVoiceCallHadClientRequestId('req-123');
});

it('asserts a call matched a callback', function (): void {
    Africastalking::voice()->call('+254700111222')->say('Hi')->send();

    Africastalking::fake()->assertVoiceCallPlaced(
        fn(array $body) => in_array('+254700111222', $body['to'], true),
    );
});

it('asserts the call actions, in order', function (): void {
    Africastalking::voice()->call('+254700111222')
        ->say('Welcome')
        ->play('https://example.com/audio.mp3')
        ->dial(['+254700333444'])
        ->send();

    Africastalking::fake()->assertVoiceCallHadActions(['Say', 'Play', 'Dial']);
    Africastalking::fake()->assertVoiceCallSaid('Welcome');
    Africastalking::fake()->assertVoiceCallPlayed('https://example.com/audio.mp3');
    Africastalking::fake()->assertVoiceCallDialed(['+254700333444']);
});

it('fails assertVoiceCallQueued when no call action enqueued anything', function (): void {
    Africastalking::voice()->call('+254700111222')->say('Hi')->send();

    Africastalking::fake()->assertVoiceCallQueued('support');
})->throws(PHPUnit\Framework\ExpectationFailedException::class);

it('makes every voice call fail', function (): void {
    Africastalking::fake()->failVoiceCalls();

    $response = Africastalking::voice()->call('+254700111222')->say('Hi')->send();

    expect($response->errorMessage)->toBe('InsufficientBalance')
        ->and($response->recipients->first()['status'])->toBe('InvalidPhoneNumber');
});

it('makes voice calls fail only for specific numbers', function (): void {
    Africastalking::fake()->failVoiceCallsFor(['+254700111222']);

    $response = Africastalking::voice()
        ->call(['+254700111222', '+254700333444'])
        ->say('Hi')
        ->send();

    $statuses = $response->recipients->pluck('status', 'phoneNumber');

    expect($statuses->get('+254700111222'))->toBe('InvalidPhoneNumber')
        ->and($statuses->get('+254700333444'))->toBe('Queued');
});

it('seeds the queue status response', function (): void {
    Africastalking::fake()->withQueueStatus([
        ['phoneNumber' => '+254700111222', 'status' => 'Queued'],
    ]);

    $status = Africastalking::voice()->queueStatus(['+254700111222'])->get();

    expect($status)->toBeArray()
        ->and($status['entries'])->toHaveCount(1);

    Africastalking::fake()->assertQueueStatusChecked('+254700111222');
});

it('seeds the queue status response per phone number', function (): void {
    Africastalking::fake()
        ->withQueueStatus([])
        ->withQueueStatusFor('+254700111222', [
            ['phoneNumber' => '+254700111222', 'status' => 'Queued'],
        ]);

    $forSeededNumber = Africastalking::voice()->queueStatus(['+254700111222'])->get();
    $forOtherNumber = Africastalking::voice()->queueStatus(['+254700999888'])->get();

    expect($forSeededNumber['entries'])->toHaveCount(1)
        ->and($forOtherNumber['entries'])->toHaveCount(0);
});
