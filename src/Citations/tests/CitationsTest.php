<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Tool;
use Shipfastlabs\Toolkit\Citations\Citations;
use Shipfastlabs\Toolkit\Citations\CitationValidator;

it('creates a collection of all Citations tools', function (): void {
    $tools = Citations::all();

    expect($tools)->toBeInstanceOf(Collection::class)
        ->and($tools)->toHaveCount(1)
        ->and($tools->all())->toContainOnlyInstancesOf(Tool::class);
});

it('includes each Citations tool exactly once', function (): void {
    $classes = Citations::all()->map(fn ($tool): string => $tool::class);

    expect($classes->all())->toEqualCanonicalizing([
        CitationValidator::class,
    ]);
});
