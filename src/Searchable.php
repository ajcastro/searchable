<?php

namespace AjCastro\Searchable;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use AjCastro\Searchable\Search\SublimeSearch;

trait Searchable
{
    protected static $allSearchableColumns = [];

    protected $sortByRelevance = true;

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

        return static::getTableColumns($this->getTable());
    }

    /**
     * Get table columns.
     *
     * @param  string $table
     * @return array
     */
    public static function getTableColumns($table = null)
    {
        $table = $table ?? (new static)->getTable();

        if (!Arr::has(static::$allSearchableColumns, $table)) {
            static::$allSearchableColumns[$table] = Schema::getColumnListing($table);
        }

        return static::$allSearchableColumns[$table];
    }

    /**
     * Identifies if the column is a valid column, either a regular table column or derived column.
     * Useful for checking valid columns to avoid sql injection especially in orderBy query.
     *
     * @param  string  $column
     * @return boolean
     */
    public static function isColumnValid($column)
    {
        $model = new static;
        $searchableColumns = $model->searchableColumns();

        // Derived columns are a key in searchableColumns.
        if (array_key_exists($column, $searchableColumns)) {
            return true;
        }

        // Regular table column can be included in the searchableColumns.
        if (in_array($column, $searchableColumns)) {
            return true;
        }

        // Regular table column from the table
        return in_array($column, static::getTableColumns($model->getTable()));
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
     * @return void
     */
    public function scopeSearch($query, $search, $searchQuery = null)
    {
        if (is_null($searchQuery)) {
            $this->applySearchableJoins($query);
        }

        $searchQuery = $searchQuery ?: static::searchQuery();

        $searchQuery->setQuery($query)->search($search)->select($this->getTable().'.*');

        if ($query->getModel()->shouldSortByRelevance()) {
            $searchQuery->applySortByRelevance();
        }
    }

    /**
     * Scope query to set $sortByRelevance.
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  boolean $sortByRelevance
     * @return void
     */
    public function scopeSortByRelevance($query, $sortByRelevance = true)
    {
        $query->getModel()->searchableSortByRelevance($sortByRelevance);
    }

    /**
     * Set model's $sortByRelevance for searchable query.
     *
     * @param  boolean $sortByRelevance
     * @return $this
     */
    public function searchableSortByRelevance($sortByRelevance = true)
    {
        $this->sortByRelevance = $sortByRelevance;

        return $this;
    }

    /**
     * If model should sort by relevance.
     *
     * @return bool
     */
    public function shouldSortByRelevance()
    {
        return $this->sortByRelevance;
    }

    /**
     * Set $searchable.
     *
     * @param array $config
     * @return  $this
     */
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

    /**
     * Set searchable columns.
     *
     * @param array $columns
     * @return  $this
     */
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

    /**
     * Set searchable joins.
     *
     * @param array $joins
     * @return  $this
     */
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

    /**
     * Add searchable.
     *
     * @param array $config
     * @return  $this
     */
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

    /**
     * Add searchable columns.
     *
     * @param array $config
     * @return  $this
     */
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

    /**
     * Add searchable joins.
     *
     * @param array $config
     * @return  $this
     */
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
