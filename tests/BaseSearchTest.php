<?php

namespace Tests;

use AjCastro\Searchable\BaseSearch;
use AjCastro\Searchable\Columns;
use Faker\Provider\Base;

class BaseSearchTest extends \Orchestra\Testbench\TestCase
{
    protected BaseSearch $search;

    public function setUp(): void
    {
        parent::setUp();

        $this->search = new BaseSearch(
            Post::query(),
            Columns::make([
                'posts.title',
                'description',
                'author_name' => 'authors.name',
                'authors.age as author_age',
            ]),
        );
    }

    public function test_can_initialize_base_search_and_perform_a_search()
    {
        $this->assertInstanceOf(BaseSearch::class, $this->search);
        $sql = $this->search->search('My Daily Posts')->toSql();
        $this->assertTrue(is_string($sql)); // we just check if it parse successfully
    }

    public function test_columns_to_compare_using_where()
    {
        $this->assertEquals([
            'posts.title',
            'description',
            'authors.name',
            'authors.age',
        ], $this->search->columnsToCompare());
    }

    public function test_columns_to_compare_using_having()
    {
        $this->search->setSearchOperator('having');

        $this->assertEquals([
            'title',
            'description',
            'author_name',
            'author_age',
        ], $this->search->columnsToCompare());
    }
}

class Post extends \Illuminate\Database\Eloquent\Model
{
}
