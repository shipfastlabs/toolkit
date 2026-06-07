<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Tool;
use Shipfastlabs\Toolkit\Tavily\Tavily;
use Shipfastlabs\Toolkit\Tavily\TavilyCrawl;
use Shipfastlabs\Toolkit\Tavily\TavilyExtract;
use Shipfastlabs\Toolkit\Tavily\TavilyMap;
use Shipfastlabs\Toolkit\Tavily\TavilySearch;

it('creates a collection of all Tavily tools', function (): void {
    $tools = Tavily::all();

    expect($tools)->toBeInstanceOf(Collection::class)
        ->and($tools)->toHaveCount(4)
        ->and($tools->all())->toContainOnlyInstancesOf(Tool::class);
});

it('includes each Tavily tool exactly once', function (): void {
    $classes = Tavily::all()->map(fn ($tool): string => $tool::class);

    expect($classes->all())->toEqualCanonicalizing([
        TavilySearch::class,
        TavilyExtract::class,
        TavilyCrawl::class,
        TavilyMap::class,
    ]);
});
