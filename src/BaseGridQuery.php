<?php

namespace SedpMis\BaseGridQuery;

use SedpMis\Lib\PageLimitOffset;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\DB as DB;
use SedpMis\BaseGridQuery\Search\BasicSearch;

abstract class BaseGridQuery
{
    /**
     * Initialized query.
     *
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $query;

    /**
     * If the grid query is auto paginated. Useful for paginated rest-api.
     *
     * @var bool
     */
    protected $paginated = false;

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

        if ($this->paginated) {
            $query->limit($this->paginator()->limit());
            $query->offset($this->paginator()->offset());
        }

        return $query;
    }

    /**
     * Set if auto-paginated.
     *
     * @param  bool $paginated
     * @return $this
     */
    public function paginate($paginated = true)
    {
        $this->paginated = $paginated;

        return $this;
    }

    /**
     * Return a paginator returning limit and offset base from the request query parameters `page` and `per_page`.
     *
     * @return mixed
     */
    public function paginator()
    {
        return new PageLimitOffset(Request::get('per_page', 15), Request::get('page', 1));
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
     * Apply a search query.
     *
     * @param  string $searchStr
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function search($searchStr)
    {
        $searcher = new BasicSearch($this);

        return $searcher->search($searchStr);
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
     * Get the names of columns to be used in the query result.
     *
     * @return array
     */
    public function columnNames()
    {
        $columnNames = [];

        foreach ($this->columns() as $key => $column) {
            if (is_string($key)) {
                $columnNames[] = $key;
            } elseif (str_contains($column, '.')) {
                list($table, $columnName) = explode('.', $column);
                $columnNames[]            = $columnName;
            } else {
                $columnNames[] = $column;
            }
        }

        return $columnNames;
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
