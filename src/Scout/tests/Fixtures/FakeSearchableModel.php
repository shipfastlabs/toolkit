<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Scout\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class FakeSearchableModel extends Model
{
    public $incrementing = false;

    protected $guarded = [];

    /**
     * @return object{take: callable(int): object, get: callable(): Collection<int, FakeSearchableModel>}
     */
    public static function search(string $query): object
    {
        $records = [
            self::makeRecord(1, 'Laravel', 'laravel'),
            self::makeRecord(2, 'Laravel AI', 'laravel-ai'),
            self::makeRecord(3, 'Toolkit', 'toolkit'),
        ];

        return new class($records)
        {
            /**
             * @param  list<FakeSearchableModel>  $records
             */
            public function __construct(private array $records) {}

            public function take(int $limit): self
            {
                $this->records = array_slice($this->records, 0, $limit);

                return $this;
            }

            /**
             * @return Collection<int, FakeSearchableModel>
             */
            public function get(): Collection
            {
                return collect($this->records);
            }
        };
    }

    private static function makeRecord(int $id, string $name, string $slug): self
    {
        $model = new self;
        $model->forceFill([
            'id' => $id,
            'name' => $name,
            'slug' => $slug,
        ]);
        $model->exists = true;

        return $model;
    }
}
