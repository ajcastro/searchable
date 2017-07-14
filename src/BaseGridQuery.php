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
     * If the grid query is auto paginated. Useful for paginated rest-api.
     *
     * @var bool
     */
    protected $paginated = false;

    /**
     * Default number of items per page.
     *
     * @var int
     */
    protected $perPage = 15;

    /**
     * Initial page.
     *
     * @var int
     */
    protected $page = 1;

    /**
     * Search operator.
     * Whether to use where or having in query to compare columns against search string.
     * Values: where, having.
     *
     * @var string
     */
    protected $searchOperator = 'having';

    /**
     * If searching will be sorted by sort_index.
     *
     * @var bool
     */
    protected $sortSearch = true;

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
     * Set if auto-paginated.
     *
     * @param  bool $paginated
     * @return $this
     */
    public function paginated($paginated = true)
    {
        $this->paginated = $paginated;

        return $this;
    }

    /**
     * Set per page and page parameters.
     *
     * @param  int  $perPage
     * @param  int $page
     * @return $this
     */
    public function paginate($perPage = null, $page = 1)
    {
        $this->paginated(true);

        $this->perPage = $perPage ?: $this->perPage;
        $this->page    = $page;

        return $this;
    }

    /**
     * Return the number of per page items.
     *
     * @return int
     */
    public function perPage()
    {
        return Request::get('per_page', $this->perPage);
    }

    /**
     * Return the current page.
     *
     * @return int
     */
    public function page()
    {
        return Request::get('page', $this->page);
    }

    /**
     * Return a page limitter returning limit and offset base from page and per_page parameters.
     *
     * @return mixed
     */
    public function pageLimitter()
    {
        return new PageLimitOffset($this->perPage(), $this->page());
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
     * Apply a search query.
     *
     * @param  string $searchStr
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function search($searchStr)
    {
        return $this->searcher()->search($searchStr);
    }

    /**
     * Prepare and return the searchable query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function searchableQuery()
    {
        $query = $this->makeQuery();

        if ($this->paginated) {
            $query->limit($this->pageLimitter()->limit());
            $query->offset($this->pageLimitter()->offset());
        }

        return $query;
    }

    /**
     * Return a searcher, the search query logic and algorithm.
     *
     * @return mixed
     */
    public function searcher()
    {
        return new SublimeSearch(
            $this->searchableQuery(),
            $this->searchOperator === 'having' ? $this->columnKeys() : array_values($this->columns()),
            $this->sortSearch,
            method_exists($this, 'sortColumns') ? $this->sortColumns() : $this->columns(),
            $this->searchOperator
        );
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
     * Set sortSearch value.
     *
     * @param  bool $bool
     * @return $this
     */
    public function sortSearch($sortSearch = true)
    {
        $this->sortSearch = $sortSearch;

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
