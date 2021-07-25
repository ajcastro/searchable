<?php

namespace AjCastro\Searchable;

use AjCastro\Searchable\Columns;
use AjCastro\Searchable\SearchParsers\CustomSearch;
use AjCastro\Searchable\SearchParsers\FuzzySearch;
use AjCastro\Searchable\SearchParsers\ParserInterface;
use Illuminate\Database\Eloquent\Builder;

class BaseSearch
{
    public Builder $query;

    public Columns $columns;

    /**
     * Search operator.
     * Whether to use where or having in query to compare columns against search string.
     * Values: where, having.
     *
     * @var string
     */
    protected $searchOperator = 'where';

    /**
     * Search string.
     * This is set everytime search() is called.
     *
     * @var string
     */
    protected $searchStr;

    /**
     * Default search string parser.
     *
     * @var \AjCastro\Searchable\Search\ParserInterface
     */
    protected ParserInterface $defaultParser;

    public function __construct(Columns $columns)
    {
        $this->columns = $columns;
    }

    public static function make(Columns $columns)
    {
        return new static($columns);
    }

    /**
     * Apply a search query.
     *
     * @param  string $searchStr
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function search($searchStr)
    {
        $this->searchStr  = $searchStr;
        $columnsToCompare = $this->columnsToCompare();
        $conditions       = [];
        $query            = $this->query;

        if (count($columnsToCompare) === 0) {
            return $query;
        }

        $parsedStr = $this->parseSearchStr($searchStr);

        foreach ($columnsToCompare as $column) {
            $conditions[] = $column . ' like "' . $parsedStr . '"';
        }

        $method = $this->searchOperator . 'Raw';
        $query->{$method}('(' . join(' OR ', $conditions) . ')');

        return $query;
    }

    /**
     * Parse string to search.
     *
     * @param  string|mixed $searchStr
     * @return string
     */
    protected function parseSearchStr($searchStr)
    {
        return $this->getDefaultParser()->parse($searchStr);
    }

    /**
     * Get default search string parser.
     *
     * @return \AjCastro\Searchable\Search\ParserInterface
     */
    public function getDefaultParser(): ParserInterface
    {
        if (isset($this->defaultParser)) {
            return $this->defaultParser;
        }

        return new FuzzySearch;
    }

    /**
     * Set a custom search string parser via callback
     *
     * @param callable $callback
     * @return $this
     */
    public function parseUsing(callable $callback)
    {
        $this->defaultParser = new CustomSearch($callback);

        return $this;
    }

    /**
     * Set query.
     *
     * @param Builder $query
     * @return $this
     */
    public function setQuery(Builder $query)
    {
        $this->query = $query;

        return $this;
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
     * Return the searchable columns to compare, actual columns for `where` operator and alias column names for `having` operator.
     *
     * @return array
     */
    public function columnsToCompare()
    {
        return $this->searchOperator === 'having' ? $this->columns->keys() : $this->columns->actual();
    }

    /**
     * Return the search string.
     *
     * @return string
     */
    public function getSearchStr()
    {
        return $this->searchStr;
    }
}
