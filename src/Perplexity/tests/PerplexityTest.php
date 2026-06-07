<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Tool;
use Shipfastlabs\Toolkit\Perplexity\Perplexity;
use Shipfastlabs\Toolkit\Perplexity\PerplexityAsk;
use Shipfastlabs\Toolkit\Perplexity\PerplexitySearch;

it('creates a collection of all Perplexity tools', function (): void {
    $tools = Perplexity::all();

    expect($tools)->toBeInstanceOf(Collection::class)
        ->and($tools)->toHaveCount(2)
        ->and($tools->all())->toContainOnlyInstancesOf(Tool::class);
});

it('includes each Perplexity tool exactly once', function (): void {
    $classes = Perplexity::all()->map(fn ($tool): string => $tool::class);

    expect($classes->all())->toEqualCanonicalizing([
        PerplexitySearch::class,
        PerplexityAsk::class,
    ]);
});
