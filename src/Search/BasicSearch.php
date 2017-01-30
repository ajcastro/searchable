<?php

namespace SedpMis\BaseGridQuery\Search;

use SedpMis\BaseGridQuery\BaseGridQuery;
use DB;

class BasicSearch
{
    /**
     * Searchable columns.
     *
     * @var array
     */
    protected $searchable = [];

    /**
     * Grid query instance.
     *
     * @var \SedpMis\BaseGridQuery\BaseGridQuery
     */
    protected $gridQuery;

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
     * Construct.
     *
     * @param \SedpMis\BaseGridQuery\BaseGridQuery $gridQuery
     * @param array $searchable
     * @param bool $sort
     */
    public function __construct($gridQuery, $searchable = [], $sort = true)
    {
        $this->gridQuery  = ($gridQuery instanceof BaseGridQuery) ? $gridQuery : null;
        $this->query      = (!$gridQuery instanceof BaseGridQuery) ? $gridQuery : null; // assume as query builder when it is not gridQuery
        $this->searchable = $searchable;
        $this->sort       = $sort;
    }

    /**
     * Return searchable column names.
     *
     * @return array
     */
    public function searchable()
    {
        return $this->searchable ?: array_keys($this->gridQuery->columns());
    }

    /**
     * Return the query for the search.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        return $this->query ?: $this->gridQuery->makeQuery();
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

        $searchStr = $this->parseSearchStr($searchStr);

        foreach ($this->searchable() as $column) {
            $conditions[] = $column.' like "'.$searchStr.'"';
        }

        $query = $this->query()->havingRaw('('.join(' OR ', $conditions).')');

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
     * Return the grid query instance.
     *
     * @return \SedpMis\BaseGridQuery\BaseGridQuery
     */
    public function gridQuery()
    {
        return $this->gridQuery;
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
     * Apply sort in query. By default using mysql locate function.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $searchStr
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applySort($query, $searchStr)
    {
        $sqls          = [];
        $concatColumns = 'CONCAT('.join(',', $this->searchable()).')';

        for ($i = 0, $j = strlen($searchStr); $i < $j; $i++) {
            $character = $searchStr[$i];

            $counter = $i + 1;
            $sqls[]  = "LOCATE('".addslashes($character)."', {$concatColumns}, {$counter})";
        }

        $query->addSelect(DB::raw('('.implode('+', $sqls).') AS search_position'));
        $query->orderBy('search_position', 'asc');

        return $query;
    }
}
