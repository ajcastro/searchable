<?php

namespace AjCastro\Searchable;

use AjCastro\Searchable\Columns;
use AjCastro\Searchable\SearchParsers\CustomSearch;
use AjCastro\Searchable\SearchParsers\FuzzySearch;
use AjCastro\Searchable\SearchParsers\ParserInterface;
use AjCastro\Searchable\SortByRelevance;
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

    /**
     * If searching will be sorted by _score.
     * This is the relevance score of the search string.
     *
     * @var bool
     */
    protected bool $sortByRelevance = true;

    public function __construct(Builder $query, Columns $columns, bool $sortByRelevance = true, $searchOperator = 'where')
    {
        $this->query = $query;
        $this->columns = $columns;
        $this->sortByRelevance($sortByRelevance);
        $this->searchOperator = $searchOperator;
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

        if ($this->shouldSortByRelevance()) {
            $this->applySortByRelevance();
        }

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
     * Set sort by relevance boolean.
     *
     * @param  bool $bool
     * @return $this
     */
    public function sortByRelevance($sortByRelevance = true)
    {
        $this->sortByRelevance = $sortByRelevance;

        return $this;
    }

    /**
     * Whether this search query should sort by relevance with key of `sort_index`.
     *
     * @return boolean
     */
    public function shouldSortByRelevance()
    {
        return $this->sortByRelevance;
    }

    public function applySortByRelevance()
    {
        SortByRelevance::sort($this->query, $this->sortColumns(), $this->searchStr);
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
     * Return the columns for sorting query.
     *
     * @return array
     */
    protected function sortColumns()
    {
        return $this->columns->keys();
    }
}
