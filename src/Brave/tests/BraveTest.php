<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Tool;
use Shipfastlabs\Toolkit\Brave\Brave;
use Shipfastlabs\Toolkit\Brave\BraveSearch;

it('creates a collection of all Brave tools', function (): void {
    $tools = Brave::all();

    expect($tools)->toBeInstanceOf(Collection::class)
        ->and($tools)->toHaveCount(1)
        ->and($tools->all())->toContainOnlyInstancesOf(Tool::class);
});

it('includes each Brave tool exactly once', function (): void {
    $classes = Brave::all()->map(fn ($tool): string => $tool::class);

    expect($classes->all())->toEqualCanonicalizing([
        BraveSearch::class,
    ]);
});
