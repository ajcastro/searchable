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

        if (!array_key_exists($table = $this->getTable(), static::$allSearchableColumns)) {
            static::$allSearchableColumns[$table] = Schema::getColumnListing($table);
        }

        return static::$allSearchableColumns[$table];
    }

    /**
     * Return the search query.
     *
     * @return mixed|\SedpMis\BaseGridQuery\Search\SublimeSearch
     */
    public static function searchQuery()
    {
        return new SublimeSearch($model = new static, $model->searchableColumns(), true, [], 'where');
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
        $searchQuery = $searchQuery ?: static::searchQuery($query);

        return $searchQuery->setQuery($query)->search($search)->select($this->getTable().'.*');
    }
}
