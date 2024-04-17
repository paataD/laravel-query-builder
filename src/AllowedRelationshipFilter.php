<?php

namespace Spatie\QueryBuilder;

use Illuminate\Support\Collection;
use Spatie\QueryBuilder\Contracts\AllowedFilterContract;

class AllowedRelationshipFilter implements AllowedFilterContract
{
    /** @var string */
    protected string $relationship;

    /** @var Collection */
    protected Collection $allowedFilters;

    public function __construct(string $relationship, AllowedFilterContract ...$allowedFilters)
    {
        $this->relationship = $relationship;

        $this->allowedFilters = collect($allowedFilters)->map(function ($filter) use ($relationship) {
            $filter->name = $relationship . '.' . $filter->name;
            return $filter;
        });
    }

    public static function group(string $relationship, AllowedFilterContract ...$allowedFilters): self
    {
        return new static($relationship, ...$allowedFilters);
    }

    public function filter(QueryBuilder $query, $value)
    {
        $query->where(function ($query) use ($value) {

            $this->allowedFilters->each(
                function (AllowedFilterContract $allowedFilter) use ($query, $value) {
                    $allowedFilter->filter(
                        QueryBuilder::for($query),
                        $allowedFilter->getValueFromCollection($value)
                    );
                }
            );
        });
    }

    public function getNames(): array
    {
        return $this->allowedFilters->map(
            fn(AllowedFilterContract $allowedFilter) => $allowedFilter->getNames()
        )->flatten()->toArray();
    }

    public function isRequested(QueryBuilderRequest $request): bool
    {
        return $request->filters()->hasAny($this->getNames());
    }

    public function getValueFromRequest(QueryBuilderRequest $request): Collection
    {
        return $request->filters()->only($this->getNames());
    }

    public function getValueFromCollection(Collection $value): Collection
    {
        return $value->only($this->getNames());
    }

    public function hasDefault(): bool
    {
        return false;
    }

    public function getDefault(): mixed
    {
        return null;
    }
}
