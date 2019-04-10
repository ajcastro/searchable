<?php

namespace AjCastro\Searchable;

trait SortTrait
{
    /**
     * If searching will be sorted by sort_index.
     * This is the relevance score of the search string.
     *
     * @var bool
     */
    protected $sort = true;

    /**
     * Set sort boolean.
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
     * Set sort boolean.
     *
     * @param  bool $bool
     * @return $this
     */
    public function sortByRelevance($sort = true)
    {
        return $this->sort($sort);
    }

    /**
     * Whether this search query should sort by relevance with key of `sort_index`.
     *
     * @return boolean
     */
    public function shouldSortByRelevance()
    {
        return $this->sort;
    }

    /**
     * Apply sorting query by relevance to the search.
     * By default using mysql locate function.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $searchStr
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function applySortByRelevance()
    {
        if (!method_exists($this, 'sortColumns')) {
            throw new \Exception("Using SortTrait requires sortColumns() method.");
        }

        SortByRelevance::sort($this->query, $this->sortColumns(), $this->searchStr);
    }
}
