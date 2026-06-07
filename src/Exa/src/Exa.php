<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Exa;

use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Tool;

class Exa
{
    /**
     * @return Collection<int, Tool>
     */
    public static function all(): Collection
    {
        return new Collection([
            new ExaSearch,
            new ExaFindSimilar,
            new ExaGetContents,
            new ExaAnswer,
        ]);
    }
}
