<?php

declare(strict_types=1);

namespace SamuelMwangiW\Africastalking\Testing;

use Illuminate\Support\ServiceProvider;
use SamuelMwangiW\Africastalking\Africastalking as BaseAfricastalking;

class AfricastalkingTestingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            abstract: BaseAfricastalking::class,
            concrete: fn() => new FakeableAfricastalking(),
        );
    }
}
