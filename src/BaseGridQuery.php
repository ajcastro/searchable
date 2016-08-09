<?php

namespace SedpMis\BaseGridQuery;

use Illuminate\Support\Facades\DB as DB;

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
        return $this->query()->select($this->makeSelect($this->columns()));
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
     * Set the created columns of the reportGrid to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function setSelectQuery($query)
    {
        return $query->select($this->makeSelect($this->columns));
    }

    /**
     * Get the actual column of the given column name.
     *
     * @param  string $columnName
     * @return string|mixed
     */
    public function getColumn($columnName)
    {
        $columns = $this->columns();

        if (array_key_exists($columnName, $columns)) {
            return $columns[$columnName];
        }

        foreach ($columns as $column) {
            if ($column === $columnName || ends_with($column, ".{$columnName}")) {
                return $column;
            }
        }
    }

    /**
     * Get the actual columns of the given column names.
     *
     * @param  array  $columnNames
     * @return array
     */
    public function getColumns(array $columnNames)
    {
        $columns = [];

        foreach ($columnNames as $columnName) {
            $columns[] = $this->getColumn($columnName);
        }

        return $columns;
    }

    /**
     * Getter for column.
     *
     * @param  string $columnName
     * @return string|mixed
     */
    public function __get($columnName)
    {
        return $this->getColumn($columnName);
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
     * Columns declaration of the report grid.
     *
     * @return array
     */
    abstract public function columns();

    /**
     * Initialize query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function initQuery()
    {
        throw new \Exception('Please create self initQuery() method on '.get_class($this).'.');
    }
}
