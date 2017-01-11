<?php

namespace SedpMis\BaseGridQuery\Search;

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
     * Construct.
     *
     * @param \SedpMis\BaseGridQuery\BaseGridQuery $gridQuery
     */
    public function __construct($gridQuery)
    {
        $this->gridQuery = $gridQuery;
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
     * Apply search query.
     *
     * @param  string|mixed  $input
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function search($input)
    {
        $conditions = [];

        foreach ($this->searchable() as $column) {
            $conditions[] = $column.' like "'.$this->parseInput($input).'"';
        }

        return $this->gridQuery->makeQuery()->havingRaw('('.join(' OR ', $conditions).')');
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
     * Return the grid query instance.
     *
     * @return \SedpMis\BaseGridQuery\BaseGridQuery
     */
    public function gridQuery()
    {
        return $this->gridQuery;
    }

    /**
     * Parse input to search.
     *
     * @param  string|mixed $input
     * @return string
     */
    protected function parseInput($input)
    {
        return '%'.join('%', str_split($input)).'%';
    }
}
