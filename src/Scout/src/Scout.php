<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Scout;

use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Tool;

class Scout
{
    /**
     * @return Collection<int, Tool>
     */
    public static function all(): Collection
    {
        return new Collection([
            new ScoutSearch,
        ]);
    }
}
