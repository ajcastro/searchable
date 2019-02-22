<?php

namespace SedpMis\BaseGridQuery;

use Illuminate\Support\Facades\DB;

class SortByRelevance
{
    public static function sort($query, $sortColumns, $searchStr)
    {
        if (empty($searchStr) || count($sortColumns) == 0) {
            return $query;
        }

        $sortColumns = array_map(function ($column) {
            return DB::raw("IFNULL(({$column}), '')");
        }, $sortColumns);

        $sqls              = [];
        $concatSortColumns = 'CONCAT('.join(',', $sortColumns).')';

        for ($i = 0, $j = strlen($searchStr); $i < $j; $i++) {
            $character = $searchStr[$i];

            $counter = $i + 1;
            $sqls[]  = "LOCATE('".addslashes($character)."', {$concatSortColumns}, {$counter})";
        }

        $query->addSelect(DB::raw('('.implode('+', $sqls).') AS sort_index'));
        $query->orderBy('sort_index', 'asc');

        return $query;
    }
}
