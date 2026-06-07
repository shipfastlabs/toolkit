<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Perplexity;

use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Tool;

class Perplexity
{
    /**
     * @return Collection<int, Tool>
     */
    public static function all(): Collection
    {
        return new Collection([
            new PerplexitySearch,
            new PerplexityAsk,
        ]);
    }
}
