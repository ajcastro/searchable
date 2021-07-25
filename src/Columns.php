<?php

namespace AjCastro\Searchable;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A smart columns object that let you reference actual table columns with key names.
 */
class Columns
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

    protected array $selects = [];
    protected array $keys = [];
    protected array $cache;

    public function __construct(array $columns)
    {
        $this->columns = $columns;
    }

    public static function make(array $columns)
    {
        return new static($columns);
    }

    /**
     * Return the columns as a valid select array for query builder's select() method.
     */
    public function selects(): array
    {
        if (! empty($this->selects)) return $this->selects;

        foreach ($this->columns as $key => $select) {
            if (is_string($key)) {
                $select = $select . ' as ' . $key;
            }
            if (Str::contains($select, ' as ')) {
                $select = DB::raw($select);
            }
            $this->selects[] = $select;
        }

        return $this->selects;
    }

    /**
     * Find the real actual column representing the column in the database table.
     *
     * @param string $key
     * @return mixed
     */
    public function find($key)
    {
        if (array_key_exists($key, $this->columns)) {
            return $this->columns[$key];
        }

        if ($this->cache[$key] ?? null) {
            return $this->cache[$key];
        }

        foreach ($this->columns as $column) {
            if ($column === $key || Str::endsWith($column, ".{$key}")) {
                return $this->cache[$key] = $column;
            }

            if (Str::endsWith($column, " as {$key}")) {
                return $this->cache[$key] = str_replace(" as {$key}", '', $column);
            }
        }
    }

    /**
     * Return the valid column keys which can be used as reference name for query sort.
     *
     * @return array
     */
    public function keys(): array
    {
        if (! empty($this->keys)) return $this->keys;

        foreach ($this->selects() as $select) {
            $this->keys[] = static::extractKeyFromSelect($select);
        }

        return $this->keys;
    }

    public static function extractKeyFromSelect(string $select): string
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

    public function __get($key)
    {
        return $this->find($key);
    }
}


