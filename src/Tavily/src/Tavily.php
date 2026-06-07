<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Tavily;

use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Tool;

class Tavily
{
    /**
     * @return Collection<int, Tool>
     */
    public static function all(): Collection
    {
        return new Collection([
            new TavilySearch,
            new TavilyExtract,
            new TavilyCrawl,
            new TavilyMap,
        ]);
    }
}
