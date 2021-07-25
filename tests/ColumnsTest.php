<?php

namespace Tests;

use AjCastro\Searchable\Columns;

class ColumnsTest extends \Orchestra\Testbench\TestCase
{
    public function test_find_can_return_the_actual_column()
    {
        $columns = Columns::make([
            'posts.title',
            'description',
            'author_name' => 'authors.name',
            'authors.age as author_age',
        ]);

        $this->assertEquals('posts.title', $columns->title);
        $this->assertEquals('description', $columns->description);
        $this->assertEquals('authors.name', $columns->author_name);
        $this->assertEquals('authors.age', $columns->author_age);
    }

    public function test_selects_can_return_correct_select()
    {
        $columns = Columns::make([
            'posts.title',
            'description',
            'author_name' => 'authors.name',
            'authors.age as author_age',
        ]);

        $asserts = [
            'posts.title',
            'description',
            'authors.name as author_name',
            'authors.age as author_age',
        ];

        $selects = $columns->selects();

        foreach ($asserts as $index => $assert) {
            $this->assertEquals($assert, $selects[$index]);
        }
    }

    public function test_keys_should_return_correct_keys()
    {
        $columns = Columns::make([
            'posts.title',
            'description',
            'author_name' => 'authors.name',
            'authors.age as author_age',
        ]);

        $asserts = [
            'title',
            'description',
            'author_name',
            'author_age',
        ];

        $keys = $columns->keys();

        foreach ($asserts as $index => $assert) {
            $this->assertEquals($assert, $keys[$index]);
        }
    }
}
