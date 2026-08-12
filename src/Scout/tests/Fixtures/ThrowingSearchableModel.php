<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Scout\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class ThrowingSearchableModel extends Model
{
    public static function search(string $query): object
    {
        return new class
        {
            public function take(int $limit): self
            {
                return $this;
            }

            public function get(): never
            {
                throw new RuntimeException('boom');
            }
        };
    }
}
