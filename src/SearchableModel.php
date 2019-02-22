<?php

namespace SedpMis\BaseGridQuery;

use Illuminate\Support\Facades\Schema;
use SedpMis\BaseGridQuery\Search\SublimeSearch;

trait SearchableModel
{
    protected static $allSearchableColumns = [];

    /**
     * Return the searchable columns for this model's table.
     *
     * @return array
     */
    public function searchableColumns()
    {
        if (property_exists($this, 'searchableColumns')) {
            return $this->searchableColumns;
        }

        if (property_exists($this, 'searchable') && array_key_exists('columns', $this->searchable)) {
            return $this->searchable['columns'];
        }

        if (!array_key_exists($table = $this->getTable(), static::$allSearchableColumns)) {
            static::$allSearchableColumns[$table] = Schema::getColumnListing($table);
        }

        return static::$allSearchableColumns[$table];
    }

    /**
     * Return the searchable joins for the search query.
     *
     * @return array
     */
    public function searchableJoins()
    {
        if (property_exists($this, 'searchableJoins')) {
            return $this->searchableJoins;
        }

        if (property_exists($this, 'searchable') && array_key_exists('joins', $this->searchable)) {
            return $this->searchable['joins'];
        }

        return [];
    }

    protected function applySearchableJoins($query)
    {
        foreach ($this->searchableJoins() as $table => $join) {
            $query->leftJoin($table, $join[0], '=', $join[1]);
        }
    }

    /**
     * Return the search query.
     *
     * @return mixed|\SedpMis\BaseGridQuery\Search\SublimeSearch
     */
    public static function searchQuery()
    {
        $model = new static;

        if (method_exists($model, 'defaultSearchQuery')) {
            return $model->defaultSearchQuery();
        }

        return new SublimeSearch($model, $model->searchableColumns(), true, 'where');
    }

    /**
     * Apply search in the query.
     *
     * @param  query $query
     * @param  string $search
     * @param  \SedpMis\BaseGridQuery\BaseSearchQuery $searchQuery
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $search, $searchQuery = null)
    {
        $searchQuery = $searchQuery ?: static::searchQuery();

        $this->applySearchableJoins($query);

        $searchQuery->setQuery($query)->search($search)->select($this->getTable().'.*');

        if ($searchQuery->hasSort()) {
            $searchQuery->sortByRelevance($query);
        }
    }
}
