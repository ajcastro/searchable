<?php

namespace Tests\Stubs;

use AjCastro\Searchable\Searchable;

class Post extends \Illuminate\Database\Eloquent\Model
{
    use Searchable;

    protected $searchable = [];
}
