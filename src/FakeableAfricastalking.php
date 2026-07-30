<?php

declare(strict_types=1);

namespace SamuelMwangiW\Africastalking\Testing;

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

    public function fake(): FakeAfricastalking
    {
        return $this->fake ??= FakeAfricastalking::setUp();
    }
}
