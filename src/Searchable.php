<?php

namespace AjCastro\Searchable;

trait Searchable
{
    use WithSearchableColumns;

    protected $searchableEnabled = true;

    protected $searchQuery;

    /**
     * Apply searchable joins for the search query.
     *
     * @param  $query
     * @return void
     */
    protected function applySearchableJoins($query)
    {
        foreach ($this->searchableJoins() as $table => $join) {
            $joinMethod = $join[2] ?? 'leftJoin';
            $query->{$joinMethod}($table, $join[0], '=', $join[1]);
        }
    }

    /**
     * Return the search query.
     *
     * @return \AjCastro\Searchable\BaseSearch
     */
    public function searchQuery(): BaseSearch
    {
        if ($this->searchQuery) {
            return $this->searchQuery;
        }

        if (method_exists($this, 'defaultSearchQuery')) {
            return $this->searchQuery = $this->defaultSearchQuery();
        }

        return $this->searchQuery = new BaseSearch($this->buildSearchableColumns());
    }

    /**
     * Set the model's search query.
     *
     * @param  \AjCastro\Searchable\BaseSearch $searchQuery
     */
    public function setSearchQuery(BaseSearch $searchQuery)
    {
        $this->searchQuery = $searchQuery;

        return $this;
    }

    /**
     * Disable searchable in the model.
     *
     * @return $this
     */
    public function disableSearchable()
    {
        $this->searchableEnabled = false;

        return $this;
    }

    /**
     * Enable searchable in the model.
     *
     * @return $this
     */
    public function enableSearchable()
    {
        $this->searchableEnabled = true;

        return $this;
    }

    /**
     * Apply search in the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string $search
     *
     * @return void
     */
    public function scopeSearch($query, $search)
    {
        if (!$this->searchableEnabled) {
            return;
        }

        $this->applySearchableJoins($query);

        if (empty($query->getQuery()->columns)) {
            $query->select([$query->getQuery()->from.'.*']);
        }

        $query->getModel()->searchQuery()->setQuery($query)->search($search);
    }

    /**
     * Scope query to set $sortByRelevance.
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  boolean $sortByRelevance
     * @return void
     */
    public function scopeSortByRelevance($query, $sortByRelevance = true)
    {
        $query->getModel()->searchQuery()->sortByRelevance($sortByRelevance);
    }
}
