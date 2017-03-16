<?php

namespace SedpMis\BaseGridQuery\Search;

use DB;

/**
 * A search query resembling the behaviour in sublime file search (ctrl+p).
 */
class SublimeSearch
{
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
     * If query should be sorted.
     *
     * @var bool
     */
    protected $sort = true;

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
     * Apply search query.
     *
     * @param  string|mixed  $searchStr
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function search($searchStr)
    {
        $conditions = [];

        $parsedStr = $this->parseSearchStr($searchStr);

        foreach ($this->searchable() as $column) {
            $conditions[] = $column.' like "'.$parsedStr.'"';
        }

        $method = $this->searchOperator.'Raw';
        $query  = $this->query()->{$method}('('.join(' OR ', $conditions).')');

        if ($this->sort) {
            $this->applySort($query, $searchStr);
        }

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
     * Set if query should be sorted.
     *
     * @param  bool $sort
     * @return $this
     */
    public function sort($sort)
    {
        $this->sort = $sort;

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

    /**
     * Apply sort in query. By default using mysql locate function.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $searchStr
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applySort($query, $searchStr)
    {
        if (empty($searchStr) || count($sortColumns = $this->sortColumns()) == 0) {
            return $query;
        }

        $sortColumns = array_map(function ($column) {
            return DB::raw("IFNULL(({$column}), '')");
        }, $sortColumns);

        $sqls              = [];
        $concatSortColumns = 'CONCAT('.join(',', $sortColumns).')';

        for ($i = 0, $j = strlen($searchStr); $i < $j; $i++) {
            $character = $searchStr[$i];

            $counter = $i + 1;
            $sqls[]  = "LOCATE('".addslashes($character)."', {$concatSortColumns}, {$counter})";
        }

        $query->addSelect(DB::raw('('.implode('+', $sqls).') AS sort_index'));
        $query->orderBy('sort_index', 'asc');

        return $query;
    }
}
