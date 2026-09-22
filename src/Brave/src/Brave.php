<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Brave;

use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Tool;

class Brave
{
    /**
     * @return Collection<int, Tool>
     */
    public static function all(): Collection
    {
        return new Collection([
            new BraveSearch,
        ]);
    }
}
