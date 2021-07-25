<?php

namespace Tests;

use AjCastro\Searchable\Columns;
use AjCastro\Searchable\Searchable;
use Tests\Stubs\Post;

class SearchableTest extends \Orchestra\Testbench\TestCase
{
    private function postWithSearchableColumns()
    {
        return Post::make()->setSearchable([
            'columns' => [
                'posts.title',
                'description',
                'author_name' => 'authors.name',
                'authors.age as author_age',
            ],
        ]);
    }

    private function postWithSortableColumns()
    {
        return Post::make()->setSearchable([
            'sortable_columns' => [
                'posts.title',
                'description',
                'author_name' => 'authors.name',
                'authors.age as author_age',
            ],
        ]);
    }

    private function assertGetColumn(Post $post)
    {
        $this->assertEquals('posts.title', $post->getColumn('title'));
        $this->assertEquals('description', $post->getColumn('description'));
        $this->assertEquals('authors.name', $post->getColumn('author_name'));
        $this->assertEquals('authors.age', $post->getColumn('author_age'));
    }

    private function assertIsColumnValid(Post $post)
    {
        $this->assertTrue($post->isColumnValid('title'));
        $this->assertTrue($post->isColumnValid('description'));
        $this->assertTrue($post->isColumnValid('author_name'));
        $this->assertTrue($post->isColumnValid('author_age'));

        $this->assertFalse($post->isColumnValid('title_x'));
        $this->assertFalse($post->isColumnValid('description_x'));
        $this->assertFalse($post->isColumnValid('author_name_x'));
        $this->assertFalse($post->isColumnValid('author_age_x'));
    }

    public function test_Searchable_getColumn_from_searchable_columns()
    {
        $post = $this->postWithSearchableColumns();
        $this->assertGetColumn($post);
    }

    public function test_Searchable_isColumnValid_from_searchable_columns()
    {
        $post = $this->postWithSearchableColumns();
        $this->assertIsColumnValid($post);
    }
    public function test_Searchable_getColumn_from_sortable_columns()
    {
        $post = $this->postWithSortableColumns();
        $this->assertGetColumn($post);
    }

    public function test_Searchable_isColumnValid_from_sortable_columns()
    {
        $post = $this->postWithSortableColumns();
        $this->assertIsColumnValid($post);
    }
}
