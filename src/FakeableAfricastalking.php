<?php

declare(strict_types=1);

namespace SamuelMwangiW\Africastalking\Testing;

use BadMethodCallException;
use SamuelMwangiW\Africastalking\Africastalking as BaseAfricastalking;

/**
 * Decorates the package's main {@see BaseAfricastalking} class with a
 * fake() entry point, in the same spirit as Laravel's own Http::fake(),
 * Bus::fake(), Mail::fake(), etc.
 *
 * This class is bound behind the `Africastalking` facade by
 * {@see AfricastalkingTestingServiceProvider}, so `Africastalking::fake()`
 * works without any change to the main package.
 */
class FakeableAfricastalking extends BaseAfricastalking
{
    private ?FakeAfricastalking $fake = null;

    /**
     * Forwards read-only calls (assertions and recorded()) made directly
     * on the facade — e.g. Africastalking::assertSmsSentTo(...) — to the
     * active fake, so callers aren't required to write
     * Africastalking::fake()->assertSmsSentTo(...) for every assertion.
     *
     * Seeding/failure-injection methods (fail*, with*, fakeWalletBalance)
     * are intentionally not forwarded, to keep it visually obvious which
     * calls change fake behavior versus which ones only observe it — those
     * must go through Africastalking::fake()->....
     */
    public function __call(string $method, array $arguments): mixed
    {
        $isReadOnly = 'recorded' === $method || str_starts_with($method, 'assert');

        if ( ! $isReadOnly) {
            throw new BadMethodCallException(
                "Call to undefined method Africastalking::{$method}(). Did you mean Africastalking::fake()->{$method}(...)?",
            );
        }

        if (null === $this->fake) {
            throw new BadMethodCallException(
                "Call to undefined method Africastalking::{$method}(). Did you forget to call Africastalking::fake() first?",
            );
        }

        return $this->fake->{$method}(...$arguments);
    }

    public function fake(): FakeAfricastalking
    {
        return $this->fake ??= FakeAfricastalking::setUp();
    }
}
