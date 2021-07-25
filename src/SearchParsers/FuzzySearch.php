<?php

namespace AjCastro\Searchable\SearchParsers;

class FuzzySearch implements ParserInterface
{
    public function parse($searchStr)
    {
        $searchStr = preg_replace('/[^A-Za-z0-9]/', '', $searchStr);

        return '%' . join('%', str_split($searchStr)) . '%';
    }
}
