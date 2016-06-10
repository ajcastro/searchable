<?php

namespace SedpMis\Base\GridQuery;

interface GridQueryInterface
{
    /**
     * Create the query for the report grid.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function makeQuery();

    /**
     * Columns declaration of the report grid.
     *
     * @return array
     */
    public function columns();
}
