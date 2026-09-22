<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Citations;

use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Tool;

class Citations
{
    /**
     * @return Collection<int, Tool>
     */
    public static function all(): Collection
    {
        return new Collection([
            new CitationValidator,
        ]);
    }
}
