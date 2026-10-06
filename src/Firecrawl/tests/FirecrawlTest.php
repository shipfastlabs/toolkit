<?php

declare(strict_types=1);

use Laravel\Ai\Contracts\Tool;
use Shipfastlabs\Toolkit\Firecrawl\Firecrawl;

it('returns every firecrawl tool', function (): void {
    $tools = Firecrawl::all();

    expect($tools)->toHaveCount(5)
        ->and($tools->every(fn (Tool $tool): bool => $tool instanceof Tool))->toBeTrue();
});
