<?php

namespace Tests;

use AjCastro\Searchable\Columns;

class ColumnsTest extends \Orchestra\Testbench\TestCase
{
    protected Columns $columns;

    public function setUp(): void
    {
        parent::setUp();

        $this->columns = Columns::make([
            'posts.title',
            'description',
            'author_name' => 'authors.name',
            'authors.age as author_age',
        ]);
    }

    public function test_find_can_return_the_actual_column()
    {
        $this->assertEquals('posts.title', $this->columns->title);
        $this->assertEquals('description', $this->columns->description);
        $this->assertEquals('authors.name', $this->columns->author_name);
        $this->assertEquals('authors.age', $this->columns->author_age);
    }

    public function test_selects_can_return_correct_select()
    {
        $asserts = [
            'posts.title',
            'description',
            'authors.name as author_name',
            'authors.age as author_age',
        ];

        $selects = $this->columns->selects();

        foreach ($asserts as $index => $assert) {
            $this->assertEquals($assert, $selects[$index]);
        }
    }

    public function test_keys_should_return_correct_keys()
    {
        $asserts = [
            'title',
            'description',
            'author_name',
            'author_age',
        ];

        $keys = $this->columns->keys();

        foreach ($asserts as $index => $assert) {
            $this->assertEquals($assert, $keys[$index]);
        }
    }

    public function test_actual_should_return_correct_actual_columns()
    {
        $asserts = [
            'posts.title',
            'description',
            'authors.name',
            'authors.age',
        ];

        $keys = $this->columns->actual();

        foreach ($asserts as $index => $assert) {
            $this->assertEquals($assert, $keys[$index]);
        }
    }
}
