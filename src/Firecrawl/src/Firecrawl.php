<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Firecrawl;

use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Tool;

class Firecrawl
{
    /**
     * @return Collection<int, Tool>
     */
    public static function all(): Collection
    {
        return new Collection([
            new FirecrawlScrape,
            new FirecrawlMap,
            new FirecrawlSearch,
            new FirecrawlCrawl,
            new FirecrawlCrawlStatus,
        ]);
    }
}
