<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Scout\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class MixedResultsSearchableModel extends Model
{
    public $incrementing = false;

    protected $guarded = [];

    public static function search(string $query): object
    {
        $model = new self;
        $model->forceFill(['id' => 1, 'name' => 'Laravel']);
        $model->exists = true;

        return new readonly class([$model, 'skip-me'])
        {
            /**
             * @param  list<mixed>  $records
             */
            public function __construct(private array $records) {}

            public function take(int $limit): self
            {
                return $this;
            }

            /**
             * @return Collection<int, mixed>
             */
            public function get(): Collection
            {
                return collect($this->records);
            }
        };
    }
}
