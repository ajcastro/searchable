<?php

namespace SedpMis\BaseGridQuery;

class PageLimitOffset
{
    public $page;

    public $perPage;

    public function __construct($perPage = 0, $page = 0)
    {
        $this->page    = $page;
        $this->perPage = $perPage;
    }

    public function limit()
    {
        return $this->page ? $this->perPage : 0;
    }

    public function offset()
    {
        if (!$this->page) {
            return 0;
        }

        return ($this->page - 1) * $this->perPage;
    }
}
