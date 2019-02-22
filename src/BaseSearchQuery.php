<?php

namespace SedpMis\BaseGridQuery;

use SedpMis\BaseGridQuery\Search\SublimeSearch;

abstract class BaseSearchQuery extends BaseGridQuery
{
    use SortTrait;

    /**
     * Search operator.
     * Whether to use where or having in query to compare columns against search string.
     * Values: where, having.
     *
     * @var string
     */
    protected $searchOperator = 'where';

    /**
     * Search string.
     * This is set everytime search() is called.
     *
     * @var string
     */
    protected $searchStr;

    /**
     * Return the query for search.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        return $this->query;
    }

    /**
     * Prepare and return the searchable query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function searchableQuery()
    {
        return $this->query();
    }

    /**
     * Apply a search query.
     *
     * @param  string $searchStr
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function search($searchStr)
    {
        return $this->searcher()->search($this->searchStr = $searchStr);
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
            $this->sort,
            $this->sortColumns(),
            $this->searchOperator
        );
    }

    /**
     * Return the columns for sorting query.
     *
     * @return array
     */
    public function sortColumns()
    {
        return $this->columns();
    }

    /**
     * Get the keys of columns to be used in the query result.
     *
     * @return array
     */
    public function columnKeys()
    {
        $columnKeys = [];

        foreach ($this->columns() as $key => $column) {
            if (is_string($key)) {
                $columnKeys[] = $key;
            } elseif (str_contains($column, '.')) {
                list($table, $columnKey) = explode('.', $column);
                $columnKeys[]            = $columnKey;
            } else {
                $columnKeys[] = $column;
            }
        }

        return $columnKeys;
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
}
