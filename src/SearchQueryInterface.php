<?php

namespace SedpMis\BaseGridQuery;

interface SearchQueryInterface
{
    /**
     * Get the actual searchable column of the given column key.
     *
     * @param  string $columnKey
     * @return string|mixed
     */
    public function getColumn($columnKey);

    public function sort($sort = true);

    public function hasSort();
}
