<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Scout\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class MissingGetSearchableModel extends Model
{
    public static function search(string $query): object
    {
        return new class
        {
            public function take(int $limit): string
            {
                return 'not-gettable';
            }
        };
    }
}
