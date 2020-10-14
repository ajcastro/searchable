# Searchable

Pattern matching search and reusable queries in laravel.

- Currently supports MySQL only.
- Helpful for complex table queries with multiple joins and derived columns.
- Reusable queries and column definitions.

## Overview

### Pattern-matching search on eloquent models

Simple setup for searchable model and can search on derived columns.

```php
use AjCastro\Searchable\Searchable;

class Post
{
    use Searchable;

    protected $searchable = [
        // This will search on the defined searchable columns
        'columns' => [
            'posts.title',
            'posts.body',
            'author_full_name' => 'CONCAT(authors.first_name, " ", authors.last_name)'
        ],
        'joins' => [
            'authors' => ['authors.id', 'posts.author_id']
        ]
    ];

    public function author()
    {
        return $this->belongsTo(Author::class);
    }
}

// Usage
Post::search("Some title or body content or even the author's full name")
    ->with('author')
    ->paginate();
```

Imagine we have an api for a table or list that has searching and column sorting and pagination.
This is a usual setup for a table or list. The internal explanations will be available on the documentation below.
Our api call may look like this:

`
http://awesome-app.com/api/posts?per_page=10&page=1&sort_by=title&descending=true&search=SomePostTitle
`

Your code can look like this:

```php
class PostsController
{
    public function index()
    {
        return Post::sortByRelevance(!request()->bool('sort_by'))
            ->search(request('search'))
            ->when(Post::isColumnValid($sortColumn = request('sort_by')), function ($query) use ($sortColumn) {
                $query->orderBy(
                    \DB::raw(
                        (new Post)->getSortableColumn($sortColumn) ?? // valid sortable column
                        (new Post)->searchQuery()->getColumn($sortColumn) ?? // valid search column
                        $sortColumn // valid original table column
                    ),
                    request()->bool('descending') ? 'desc' : 'asc'
                );
            })
            ->paginate();
    }

}
```

## Documentation

### Installation

```
composer require ajcastro/searchable
```

### Searchable Model

```php
use AjCastro\Searchable\Searchable;

class Post extends Model
{
    use Searchable;

    /**
     * Searchable model definitions.
     */
     protected $searchable = [
        // Searchable columns of the model.
        // If this is not defined it will default to all table columns.
        'columns' => [
            'posts.title',
            'posts.body',
            'author_full_name' => 'CONCAT(authors.first_name, " ", authors.last_name)'
        ],
        // This is needed if there is a need to join other tables for derived columns.
        'joins' => [
            'authors' => ['authors.id', 'posts.author_id'], // defaults to leftJoin method of eloquent builder
            'another_table' => ['another_table.id', 'authors.another_table_id', 'join'], // can pass leftJoin, rightJoin, join of eloquent builder.
        ]
    ];

    /**
     * Can also be written like this for searchable columns.
     *
     * @var array
     */
    protected $searchableColumns = [
        'title',
        'body',
        'author_full_name' => 'CONCAT(authors.first_name, " ", authors.last_name)'
    ];

    /**
     * Can also be written like this for searchable joins.
     *
     * @var array
     */
    protected $searchableJoins = [
        'authors' => ['authors.id', 'posts.author_id']
    ];
}

// Usage
// Call search anywhere
// This only search on the defined columns.
Post::search('Some post')->paginate();
Post::where('likes', '>', 100)->search('Some post')->paginate();

```

This will addSelect field `sort_index` which will used to order or sort by relevance.
If you want to disable sort by relevance, call method `sortByRelevance(false)` before `search()` method.
Example:

```
Post::sortByRelevance(false)->search('Some post')->paginate();
Post::sortByRelevance(false)->where('likes', '>', 100)->search('Some post')->paginate();
```

### Set searchable configurations on runtime.

```php
$post = new Post;
$post->setSearchable([ // addSearchable() method is also available
    'columns' => [
        'posts.title',
        'posts.body',
    ],
    'joins' => [
        'authors' => ['authors.id', 'posts.author_id']
    ]
]);
// or
$post->setSearchableColumns([ // addSearchableColumns() method is also available
    'posts.title',
    'posts.body',
]);
$post->setSearchableJoins([ // addSearchableJoins() method is also available
    'authors' => ['authors.id', 'posts.author_id']
]);
```

### Easy Sortable Columns

You can define columns to be only sortable but not be part of search query constraint.
Just put it under `sortable_columns` as shown below .
This column can be easily access to put in `orderBy` of query builder. All searchable columns are also sortable columns.

```php
class Post {
     protected $searchable = [
        'columns' => [
            'title' => 'posts.title',
        ],
        'sortable_columns' => [
            'status_name' => 'statuses.name',
        ],
        'joins' => [
            'statuses' => ['statuses.id', 'posts.status_id']
        ]
    ];
}

// Usage

Post::search('A post title')->orderBy(Post::getSortableColumn('status_name'));
// This will only perform search on `posts`.`title` column and it will append "order by `statuses`.`name`" in the query.
// This is beneficial if your column is mapped to a different column name coming from front-end request.
```


### Custom Search Query - Exact Search Example

You can extend the class `AjCastro\Searchable\Search\SublimeSearch` a.k.a a fuzzy-search implementation.

```php
namespace App;

use AjCastro\Searchable\Search\SublimeSearch;

class ExactSearch extends SublimeSearch
{
    /**
     * {@inheritDoc}
     */
    protected function parseSearchStr($searchStr)
    {
        return $searchStr; // produces "where `column` like '{$searchStr}'"
        // or
        return "%{$searchStr}%"; // produces "where `column` like '%{$searchStr}%'"
    }
}
```

then use it in the model:

```php
namespace App;

class User extends Model
{
    public function defaultSearchQuery()
    {
        return new ExactSearch($this, $this->searchableColumns(), $this->sortByRelevance, 'where');
    }
}
```

You may also check the build query by dd-ing it:

```php
$query = User::search('John Doe');
dd($query->toSql());
```
which may output to
```
select * from users where `column` like 'John Doe'
// or
select * from users where `column` like '%John Doe%'
```

### Using derived columns for order by and where conditions

Usually we have queries that has a derived columns like our example for `Post`'s `author_full_name`.
Sometimes we need to sort our query results by this column.

```php
Post::search('Some search')->orderBy(Post::searchQuery()->author_full_name, 'desc')->paginate();
Post::search('Some search')->where(Post::searchQuery()->author_full_name, 'William%')->paginate();
```

## Helper methods available on model

### isColumnValid [static]

- Identifies if the column is a valid column, either a regular table column or derived column.
- Useful for checking valid columns to avoid sql injection especially in `orderBy` query.

```php
Post::isColumnValid(request('sort_by'));
```

### getTableColumns [static]

- Get the table columns.

```php
Post::getTableColumns();
```

### enableSearchable

- Enable the searchable behavior.

```php
$query->getModel()->enableSearchable();
$query->search('foo');
```

### disableSearchable

- Disable the searchable behavior.
- Calling `search()` method will not perform a search.

```php
$query->getModel()->disableSearchable();
$query->search('foo');
```

### setSearchable

- Set or override the model's `$searchable` property.
- Useful for building searchable config on runtime.

```php
$query->getModel()->setSearchable([
  'columns' => ['title', 'status'],
  'joins' => [...],
]);
$query->search('foo');
```

### addSearchable

- Add columns or joins in the model's `$searchable` property.
- Useful for building searchable config on runtime.

```php
$query->getModel()->addSearchable([
  'columns' => ['title', 'status'],
  'joins' => [...],
]);
$query->search('foo');
```

## Warning

Calling `select()` after `search()` will overwrite `sort_index` field, so it is recommended to call `select()`
before `search()`.

## Credits

- Ray Anthony Madrona [@raymadrona](https://github.com/raymadrona), for the tips on using MySQL `LOCATE()` for sort relevance.
- [nicolaslopezj/searchable](https://github.com/nicolaslopezj/searchable), for the `$searchable` property declaration style.
