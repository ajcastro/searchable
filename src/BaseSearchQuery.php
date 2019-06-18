<?php

namespace AjCastro\Searchable;

use AjCastro\Searchable\Search\SublimeSearch;

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
     * Search string.
     * This is set everytime search() is called.
     *
     * @var string
     */
    protected $searchStr;

    /**
     * If searching will be sorted by sort_index.
     * This is the relevance score of the search string.
     *
     * @var bool
     */
    protected $sort = true;

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
            $this->query(),
            $this->columns(),
            $this->sort,
            $this->searchOperator
        );
    }

    /**
     * Return the columns for sorting query.
     *
     * @return array
     */
    protected function sortColumns()
    {
        return $this->columns();
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
     * Alias of sortByRelevance.
     *
     * @param  bool $bool
     * @return $this
     */
    public function sort($sort = true)
    {
        return $this->sortByRelevance($sort);
    }

    /**
     * Set sort boolean.
     *
     * @param  bool $bool
     * @return $this
     */
    public function sortByRelevance($sort = true)
    {
        $this->sort = $sort;

        return $this;
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
            throw new \Exception("Sort by relevance requires sortColumns() method.");
        }

        SortByRelevance::sort($this->query, $this->sortColumns(), $this->searchStr);
    }
}
