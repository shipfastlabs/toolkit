<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Tool;
use Shipfastlabs\Toolkit\Exa\Exa;
use Shipfastlabs\Toolkit\Exa\ExaAnswer;
use Shipfastlabs\Toolkit\Exa\ExaFindSimilar;
use Shipfastlabs\Toolkit\Exa\ExaGetContents;
use Shipfastlabs\Toolkit\Exa\ExaSearch;

it('creates a collection of all Exa tools', function (): void {
    $tools = Exa::all();

    expect($tools)->toBeInstanceOf(Collection::class)
        ->and($tools)->toHaveCount(4)
        ->and($tools->all())->toContainOnlyInstancesOf(Tool::class);
});

it('includes each Exa tool exactly once', function (): void {
    $classes = Exa::all()->map(fn ($tool): string => $tool::class);

    expect($classes->all())->toEqualCanonicalizing([
        ExaSearch::class,
        ExaFindSimilar::class,
        ExaGetContents::class,
        ExaAnswer::class,
    ]);
});
