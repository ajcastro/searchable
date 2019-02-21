<?php

namespace SedpMis\BaseGridQuery;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\DB as DB;
use SedpMis\BaseGridQuery\Search\SublimeSearch;

abstract class BaseGridQuery
{
    /**
     * Initialized query.
     *
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $query;

    /**
     * Return the initialized specific query. This contains the joins logic and condition that make the query specific.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        return $this->query ?: $this->initQuery();
    }

    /**
     * Return the final query base from the query() method with its select statement from the columns() method.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function makeQuery()
    {
        $query = $this->query()->select($this->makeSelect($this->columns()));

        return $query;
    }

    /**
     * Create an array of select parameters from the columns declaration,
     * transforming string indexed element to have an alias "as".
     *
     * @param  array|null $columns
     * @return array
     */
    public function makeSelect(array $columns = null)
    {
        $columns = $columns ?: $this->columns();
        $selects = [];

        foreach ($columns as $key => $select) {
            if (is_int($key)) {
                $selects[] = $select;
            } else {
                $selects[] = DB::raw($select.' as '.$key);
            }
        }

        return $selects;
    }

    /**
     * Set the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return  $this
     */
    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Set the columns of this gridQuery instance of the grid to the given query's select clause.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function setSelectQuery($query)
    {
        return $query->select($this->makeSelect($this->columns()));
    }

    /**
     * Get the actual column of the given column key.
     *
     * @param  string $columnKey
     * @return string|mixed
     */
    public function getColumn($columnKey)
    {
        $columns = $this->columns();

        if (array_key_exists($columnKey, $columns)) {
            return $columns[$columnKey];
        }

        foreach ($columns as $column) {
            if ($column === $columnKey || ends_with($column, ".{$columnKey}")) {
                return $column;
            }
        }
    }

    /**
     * Get the actual columns of the given column keys.
     *
     * @param  array  $columnKeys
     * @return array
     */
    public function getColumns(array $columnKeys)
    {
        $columns = [];

        foreach ($columnKeys as $columnKey) {
            $columns[] = $this->getColumn($columnKey);
        }

        return $columns;
    }

    /**
     * Getter for column.
     *
     * @param  string $columnKey
     * @return string|mixed
     */
    public function __get($columnKey)
    {
        return $this->getColumn($columnKey);
    }

    /**
     * Handle dynamic calls on query.
     *
     * @param  string $method
     * @param  array $parameters
     * @return $this
     */
    public function __call($method, $parameters)
    {
        if (!$this->query) {
            throw new \Exception("Property \$query is not set. Cannot call method {$method} on object of ".static::class.'.');
        }

        call_user_func_array([$this->query, $method], $parameters);

        return $this;
    }

    /**
     * Initialize query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function initQuery()
    {
        throw new \Exception('Please create self initQuery() method on '.get_class($this).'.');
    }

    /**
     * Get the keys of columns to be used in the query result.
     *
     * @return array
     */
    public function columnKeys()
    {
        $columnKeys = [];

        foreach ($this->columns() as $key => $column) {
            if (is_string($key)) {
                $columnKeys[] = $key;
            } elseif (str_contains($column, '.')) {
                list($table, $columnKey) = explode('.', $column);
                $columnKeys[]            = $columnKey;
            } else {
                $columnKeys[] = $column;
            }
        }

        return $columnKeys;
    }

    /**
     * Columns declaration of the report grid.
     *
     * @return array
     */
    abstract public function columns();

    /**
     * Return new instance.
     *
     * @return static
     */
    public static function make()
    {
        return new static;
    }
}
