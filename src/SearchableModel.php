<?php

namespace SedpMis\BaseGridQuery;

use SedpMis\BaseGridQuery\Search\SublimeSearch;
use Illuminate\Support\Facades\Schema;

/**
 * Deprecated. Use SublimeSearch instead.
 */
class SearchableModel extends BaseGridQuery
{
    protected $model;

    protected $searchableColumns;

    public function __construct($model, $query = null, $searchableColumns = ['*'])
    {
        $this->model             = $model;
        $this->query             = $query ?: $model;
        $this->searchableColumns = $this->getSearchableColumns($model, $searchableColumns);
    }

    public function getSearchableColumns($model, $searchableColumns)
    {
        if (is_null($searchableColumns) || $searchableColumns == ['*']) {
            return Schema::getColumnListing($model->getTable());
        }

        return $searchableColumns;
    }

    public function searcher()
    {
        return new SublimeSearch(
            $this->makeQuery(),
            $this->columnKeys(),
            true,
            method_exists($this, 'sortColumns') ? $this->sortColumns() : $this->columns(),
            'having'
        );
    }

    public function columns()
    {
        return $this->searchableColumns;
    }
}
