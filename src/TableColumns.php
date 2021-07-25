<?php

namespace AjCastro\Searchable;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class TableColumns
{
    protected static $cache = [];

    /**
     * Get table columns.
     *
     * @param  string $table
     * @return array
     */
    public static function get($table)
    {
        if (! Arr::has(static::$cache, $table)) {
            static::$cache[$table] = Schema::getColumnListing($table);
        }

        return static::$cache[$table];
    }
}
