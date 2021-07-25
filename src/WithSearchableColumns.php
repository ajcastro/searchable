<?php

namespace AjCastro\Searchable;

use AjCastro\Searchable\Columns;
use AjCastro\Searchable\TableColumns;
use Illuminate\Support\Arr;

trait WithSearchableColumns
{
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

        return TableColumns::get($this->getTable());
    }

    /**
     * Return the sortable columns for this model's table.
     *
     * @return array
     */
    public function sortableColumns()
    {
        if (property_exists($this, 'sortableColumns')) {
            return $this->sortableColumns;
        }

        if (property_exists($this, 'searchable') && array_key_exists('sortable_columns', $this->searchable)) {
            return $this->searchable['sortable_columns'];
        }

        return TableColumns::get($this->getTable());
    }

    /**
     * Identifies if the column is a valid column, either a regular table column or derived column.
     * Useful for checking valid columns to avoid sql injection especially in orderBy query.
     *
     * @param  string  $column
     * @return boolean
     */
    public function isColumnValid($column)
    {
        return (bool) $this->buildAllColumns()->find($column);
    }


    /**
     * Build columns from both searchable and sortable columns
     */
    public function buildAllColumns(): Columns
    {
        return Columns::make(array_merge($this->searchableColumns(), $this->sortableColumns()));
    }

    /**
     * Build columns from searchable
     */
    public function buildSearchableColumns(): Columns
    {
        return Columns::make($this->searchableColumns());
    }

    /**
     * Build columns from sortable
     */
    public function buildSortableColumns(): Columns
    {
        return Columns::make($this->sortableColumns());
    }

    /**
     * Get the actual column from both searchable and sortable columns
     *
     * @param string $column
     * @return void
     */
    public function getColumn($column)
    {
        return $this->buildAllColumns()->find($column);
    }

    /**
     * Get the actual sortable column.
     *
     * @param  string $column
     * @return string|mixed
     */
    public function getSearchableColumn($column)
    {
        return $this->buildSearchableColumns()->find($column);
    }

    /**
     * Get the actual sortable column.
     *
     * @param  string $column
     * @return string|mixed
     */
    public function getSortableColumn($column)
    {
        return $this->buildSortableColumns()->find($column);
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
     * Set $searchable.
     *
     * @param array $config
     * @return  $this
     */
    public function setSearchable($config)
    {
        $this->setSearchableColumns(Arr::get($config, 'columns'));
        $this->setSearchableJoins(Arr::get($config, 'joins'));
        $this->setSortableColumns(Arr::get($config, 'sortable_columns'));

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
            $this->searchableColumns = $columns ?? [];
        }

        if (property_exists($this, 'searchable')) {
            $this->searchable['columns'] = $columns ?? [];
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
            $this->searchableJoins = $joins ?? [];
        }

        if (property_exists($this, 'searchable')) {
            $this->searchable['joins'] = $joins ?? [];
        }

        return $this;
    }

    /**
     * Set sortable columns.
     *
     * @param array $columns
     * @return  $this
     */
    public function setSortableColumns($columns)
    {
        if (property_exists($this, 'sortableColumns')) {
            $this->sortableColumns = $columns ?? [];
        }

        if (property_exists($this, 'searchable')) {
            $this->searchable['sortable_columns'] = $columns ?? [];
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
        if ($columns = Arr::get($config, 'columns')) {
            $this->addSearchableColumns($columns);
        }

        if ($columns = Arr::get($config, 'sortable_columns')) {
            $this->addSortableColumns($columns);
        }

        if ($joins = Arr::get($config, 'joins')) {
            $this->addSearchableJoins($joins);
        }

        return $this;
    }

    /**
     * Add searchable columns.
     *
     * @param array $columns
     * @return  $this
     */
    public function addSearchableColumns($columns)
    {
        if (property_exists($this, 'searchableColumns')) {
            $this->searchableColumns = array_merge($this->searchableColumns, $columns);
        }

        if (property_exists($this, 'searchable')) {
            $this->searchable['columns'] = array_merge($this->searchable['columns'], $columns);
        }

        return $this;
    }

    /**
     * Add searchable joins.
     *
     * @param array $joins
     * @return  $this
     */
    public function addSearchableJoins($joins)
    {
        if (property_exists($this, 'searchableJoins')) {
            $this->searchableJoins = array_merge($this->searchableJoins, $joins);
        }

        if (property_exists($this, 'searchable')) {
            $this->searchable['joins'] = array_merge($this->searchable['joins'], $joins);
        }

        return $this;
    }

    /**
     * Add sortable columns.
     *
     * @param array $columns
     * @return  $this
     */
    public function addSortableColumns($columns)
    {
        if (property_exists($this, 'sortableColumns')) {
            $this->sortableColumns = array_merge($this->sortableColumns, $columns);
        }

        if (property_exists($this, 'searchable')) {
            $this->searchable['sortable_columns'] = array_merge($this->searchable['sortable_columns'], $columns);
        }

        return $this;
    }
}
