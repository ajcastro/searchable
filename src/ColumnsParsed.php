<?php

namespace AjCastro\Searchable;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Experimental. Parsed version of Columns.
 */
class ColumnsParsed
{
    /**
     * [
     *     'posts.title',
     *     'description',
     *     'author_name' => 'authors.name',
     *     'authors.age as author_age',
     * ]
     *
     * @var array
     */
    protected array $columns;

    protected array $parsed;

    public function __construct(array $columns)
    {
        $this->columns = $columns;
        $this->parsed = static::parse($columns);
    }

    public static function parse(array $columns): array
    {
        $parsed = [];

        foreach ($columns as $key => $select) {
            if (is_string($key)) {
                $select = "{$select} as {$key}";
            }
            if (is_int($key)) {
                $key = static::parseKeyFromSelect($select);
            }
            if (Str::contains($select, ' as ')) {
                $select = static::normalizeSelect($select);
            }
            $parsed[$key] = $select;
        }

        return $parsed;
    }

    public static function parseKeyFromSelect(string $select): string
    {
        if (Str::contains($select, ' as ')) {
            [$rawSelect, $alias] = explode(' as ', $select);
            return $alias;
        }

        if (Str::contains($select, '.')) {
            [$table, $column] = explode('.', $select);
            return $column;
        }

        return $select;
    }

    public static function normalizeSelect(string $select): string
    {
        if (Str::contains($select, ' as ')) {
            [$rawSelect, $alias] = explode(' as ', $select);
            return $rawSelect;
        }

        return $select;
    }

    public function parsed()
    {
        return $this->parsed;
    }

    public function raw()
    {
        return $this->columns;
    }

    /**
     * Return the columns as a valid select array for query builder's select() method.
     */
    public function selects(): array
    {
        $selects = [];
        foreach ($this->parsed as $key => $value) {
            if (is_string($key)) {
                $selects[] = DB::raw("{$value} as {$key}");
            } else {
                $selects[] = $value;
            }
        }
        return $selects;
    }

    /**
     * Find the real actual column representing the column in the database table.
     *
     * @param string $key
     * @return mixed
     */
    public function find($key)
    {
        if (array_key_exists($key, $this->parsed)) {
            return $this->parsed[$key];
        }
    }

    /**
     * Return the valid column keys which can be used as reference name for query sort.
     *
     * @return array
     */
    public function keys(): array
    {
        return array_keys($this->parsed);
    }

    public function __get($key)
    {
        return $this->find($key);
    }
}


