<?php

namespace SedpMis\BaseGridQuery\Search;

use DB;
use SedpMis\BaseGridQuery\SortTrait;

/**
 * A search query resembling the behaviour in sublime file search (ctrl+p).
 *
 * Sample Usage:
 * (new SublimeSearch)->search()->sortByRelevance();
 */
class SublimeSearch
{
    use SortTrait;

    /**
     * Searchable columns.
     *
     * @var array
     */
    protected $searchable = [];

    /**
     * The query for the search.
     *
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $query;

    /**
     * Columns for sorting query.
     *
     * @var array
     */
    protected $sortColumns = [];

    /**
     * Search operator.
     * Whether to use where or having in query to compare columns against search string.
     * Values: where, having.
     *
     * @var string
     */
    protected $searchOperator = 'having';

    /**
     * Search string.
     * This is set everytime search() is called.
     *
     * @var string
     */
    protected $searchStr;

    /**
     * Construct.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $searchable
     * @param bool $sort
     * @param array $sortColumns
     */
    public function __construct($query, $searchable = [], $sort = true, $sortColumns = [], $searchOperator = 'having')
    {
        $this->query          = $query;
        $this->searchable     = $searchable;
        $this->sort           = $sort;
        $this->sortColumns    = $sortColumns;
        $this->searchOperator = $searchOperator;
    }

    /**
     * Return searchable column names.
     *
     * @return array
     */
    public function searchable()
    {
        return $this->searchable;
    }

    /**
     * Return the query for the search.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        return $this->query;
    }

    /**
     * Set the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return  $this
     */
    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Get the actual searchable column of the given column key.
     *
     * @param  string $columnKey
     * @return string|mixed
     */
    public function getColumn($columnKey)
    {
        $columns = $this->searchable();

        if (array_key_exists($columnKey, $columns)) {
            return $columns[$columnKey];
        }

        foreach ($columns as $column) {
            if ($column === $columnKey || ends_with($column, ".{$columnKey}")) {
                return $column;
            }
        }
    }

    /**
     * Getter for searchable column.
     *
     * @param  string $columnKey
     * @return string|mixed
     */
    public function __get($columnKey)
    {
        return $this->getColumn($columnKey);
    }

    /**
     * Apply search query.
     *
     * @param  string|mixed  $searchStr
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function search($searchStr)
    {
        $conditions = [];

        $parsedStr = $this->parseSearchStr($this->searchStr = $searchStr);

        foreach ($this->searchable() as $column) {
            $conditions[] = $column.' like "'.$parsedStr.'"';
        }

        $method = $this->searchOperator.'Raw';
        $query  = $this->query()->{$method}('('.join(' OR ', $conditions).')');

        return $query;
    }

    /**
     * Set searchable columns.
     *
     * @param array $searchable
     */
    public function setSearchable($searchable = [])
    {
        $this->searchable = $searchable;

        return $this;
    }

    /**
     * Parse string to search.
     *
     * @param  string|mixed $searchStr
     * @return string
     */
    protected function parseSearchStr($searchStr)
    {
        $searchStr = preg_replace('/[^A-Za-z0-9]/', '', $searchStr);

        return '%'.join('%', str_split($searchStr)).'%';
    }

    /**
     * Return the columns for sorting query.
     *
     * @return array
     */
    public function sortColumns()
    {
        return $this->sortColumns;
    }
}
