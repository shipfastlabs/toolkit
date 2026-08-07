<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Tool;
use Shipfastlabs\Toolkit\Scout\Scout;
use Shipfastlabs\Toolkit\Scout\ScoutSearch;

it('creates a collection of all Scout tools', function (): void {
    $tools = Scout::all();

    expect($tools)->toBeInstanceOf(Collection::class)
        ->and($tools)->toHaveCount(1)
        ->and($tools->all())->toContainOnlyInstancesOf(Tool::class);
});

it('includes each Scout tool exactly once', function (): void {
    $classes = Scout::all()->map(fn ($tool): string => $tool::class);

    expect($classes->all())->toEqualCanonicalizing([
        ScoutSearch::class,
    ]);
});
