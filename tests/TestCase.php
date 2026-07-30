<?php

declare(strict_types=1);

namespace SamuelMwangiW\Africastalking\Testing\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Saloon\Laravel\SaloonServiceProvider;
use SamuelMwangiW\Africastalking\AfricastalkingServiceProvider;
use SamuelMwangiW\Africastalking\Testing\AfricastalkingTestingServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            AfricastalkingServiceProvider::class,
            SaloonServiceProvider::class,
            AfricastalkingTestingServiceProvider::class,
        ];
    }
}
