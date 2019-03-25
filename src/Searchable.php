<?php

namespace AjCastro\Searchable;

use Illuminate\Support\Facades\Schema;
use AjCastro\Searchable\Search\SublimeSearch;

trait Searchable
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

    /**
     * Apply searchable joins for the search query.
     *
     * @param  $query
     * @return void
     */
    protected function applySearchableJoins($query)
    {
        foreach ($this->searchableJoins() as $table => $join) {
            $query->leftJoin($table, $join[0], '=', $join[1]);
        }
    }

    /**
     * Return the search query.
     *
     * @return mixed|\AjCastro\Searchable\Search\SublimeSearch
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
     * @param  \AjCastro\Searchable\BaseSearchQuery $searchQuery
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $search, $searchQuery = null)
    {
        if (is_null($searchQuery)) {
            $this->applySearchableJoins($query);
        }

        $searchQuery = $searchQuery ?: static::searchQuery();

        $searchQuery->setQuery($query)->search($search)->select($this->getTable().'.*');

        if ($searchQuery->hasSort()) {
            $searchQuery->sortByRelevance($query);
        }
    }

    public function setSearchable($config)
    {
        if ($columns = array_get($config, 'columns')) {
            $this->setSearchableColumns($columns);
        }

        if ($joins = array_get($config, 'joins')) {
            $this->setSearchableJoins($joins);
        }

        return $this;
    }

    public function setSearchableColumns($columns)
    {
        if (property_exists($this, 'searchableColumns')) {
            $this->searchableColumns = $columns;
        }

        if (property_exists($this, 'searchable')) {
            $this->searchable['columns'] = $columns;
        }

        return $this;
    }

    public function setSearchableJoins($joins)
    {
        if (property_exists($this, 'searchableJoins')) {
            $this->searchableJoins = $joins;
        }

        if (property_exists($this, 'searchable')) {
            $this->searchable['joins'] = $joins;
        }

        return $this;
    }

    public function addSearchable($config)
    {
        if ($columns = array_get($config, 'columns')) {
            $this->addSearchableColumns($columns);
        }

        if ($joins = array_get($config, 'joins')) {
            $this->addSearchableJoins($joins);
        }

        return $this;
    }

    public function addSearchableColumns($columns)
    {
        if (property_exists($this, 'searchableColumns')) {
            $this->searchableColumns = $columns + $this->searchableColumns;
        }

        if (property_exists($this, 'searchable')) {
            $this->searchable['columns'] = $columns + $this->searchable['columns'];
        }

        return $this;
    }

    public function addSearchableJoins($joins)
    {
        if (property_exists($this, 'searchableJoins')) {
            $this->searchableJoins = $joins + $this->searchableJoins;
        }

        if (property_exists($this, 'searchable')) {
            $this->searchable['joins'] = $joins + $this->searchable['joins'];
        }

        return $this;
    }
}
