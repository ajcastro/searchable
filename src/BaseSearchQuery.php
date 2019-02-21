<?php

namespace SedpMis\BaseGridQuery;

abstract class BaseSearchQuery extends BaseGridQuery
{
    /**
     * Search operator.
     * Whether to use where or having in query to compare columns against search string.
     * Values: where, having.
     *
     * @var string
     */
    protected $searchOperator = 'where';

    /**
     * If searching will be sorted by sort_index.
     *
     * @var bool
     */
    protected $sortSearch = false;

    /**
     * Apply a search query.
     *
     * @param  string $searchStr
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function search($searchStr)
    {
        return $this->searcher()->search($searchStr);
    }

    /**
     * Prepare and return the searchable query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function searchableQuery()
    {
        return $this->makeQuery();
    }

    /**
     * Return a searcher, the search query logic and algorithm.
     *
     * @return mixed
     */
    public function searcher()
    {
        return new SublimeSearch(
            $this->searchableQuery(),
            $this->searchOperator === 'having' ? $this->columnKeys() : array_values($this->columns()),
            $this->sortSearch,
            method_exists($this, 'sortColumns') ? $this->sortColumns() : $this->columns(),
            $this->searchOperator
        );
    }

    /**
     * Set search operator.
     *
     * @param  string $searchOperator
     * @return $this
     */
    public function setSearchOperator($searchOperator)
    {
        $this->searchOperator = $searchOperator;

        return $this;
    }

    /**
     * Set sortSearch value.
     *
     * @param  bool $bool
     * @return $this
     */
    public function sortSearch($sortSearch = true)
    {
        $this->sortSearch = $sortSearch;

        return $this;
    }

}
