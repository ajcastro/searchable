<?php

namespace AjCastro\Searchable\SearchParsers;

class CustomSearch implements ParserInterface
{
    protected $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function parse($searchStr)
    {
        return ($this->callback)($searchStr);
    }
}
