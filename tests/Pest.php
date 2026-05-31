<?php

declare(strict_types=1);

use Shipfastlabs\Toolkit\Tests\TestCase;

uses(TestCase::class)->in(
    ...glob(__DIR__.'/../src/*/tests') ?: [],
);
