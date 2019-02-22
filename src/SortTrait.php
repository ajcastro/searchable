<?php

namespace SedpMis\BaseGridQuery;

trait SortTrait
{
    /**
     * If searching will be sorted by sort_index.
     * This is the relevance score of the search string.
     *
     * @var bool
     */
    protected $sort = false;

    /**
     * Set sort value.
     *
     * @param  bool $bool
     * @return $this
     */
    public function sort($sort = true)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Whether this search query should sort by sort_index.
     *
     * @return boolean
     */
    public function hasSort()
    {
        return $this->sort;
    }

    /**
     * Sort the query by relevance to the search.
     * By default using mysql locate function.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $searchStr
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function sortByRelevance()
    {
        if (!method_exists($this, 'sortColumns')) {
            throw new \Exception("Using SortTrait requires sortColumns() method.");
        }

        SortByRelevance::sort($this->query, $this->sortColumns(), $this->searchStr);
    }
}
