# Searchable

Full-text search and reusable queries in laravel.

- Currently supports MySQL only.
- Helpful for complex table queries with multiple joins and derived columns.
- Reusable queries and column definitions.

## Overview

### Full-text search on eloquent models

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

Imagine we have an api for a table or list that has full-text searching and column sorting and pagination.
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
        return Post::search(request('search'))
            ->when($sortColumn = request('sort_by'), function ($query) use ($sortColumn) {
                $query->orderBy(
                    \DB::raw($this->model->searchQuery()->getColumn($sortColumn) ?? $sortColumn),
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
            'authors' => ['authors.id', 'posts.author_id']
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

### Searchable Model Custom Search Query

Sometimes our queries have lots of things and constraints to do and we can contain it in a search query class like this `PostSearch`.

```php
use AjCastro\Searchable\BaseSearchQuery;

class PostSearch extends BaseSearchQuery
{
    public function query()
    {
        // The query conditions here is always applied to our search.
        return $this->query
        ->leftJoin('authors', 'authors.id', '=', 'posts.author_id')
        ->where('posts.likes', '>', 100)
        ->where('is_active', 1)
        ->orderBy('some_column')
        // We can even access our column definition here that will result to the equivalent actual column
        // CAUTION:
        // MySQL functions need to be wrapped with DB::raw() to be parsed properly.
        // Also we can use orderByRaw() for this example.
        // Also consider wrapping it in the columns() method so it will be ready
        // everytime we use it in orderBy() or where() methods.
        ->orderBy($this->author_full_name);
    }

    public function columns()
    {
        return [
            'posts.title',
            'posts.body',
            // We wrap CONCAT() column so it will always be ready to be used in orderBy() and where() methods
            'author_full_name' => DB::raw('CONCAT(authors.first_name, " ", authors.last_name)')
        ];
    }
}

```

Then, we can use it as the default search query for the model like:

```php
class Post
{
    public function defaultSearchQuery()
    {
        return new PostSearch;
    }
}

// Usage
Post::search($searchStr)->paginate();
```

We can also use custom search query temporarily by passing it as second parameter in `search()` method.

```php
Post::search('William Shakespeare', new PostSearch)->paginate();
```

### Using derived columns for order by and where conditions

Usually we have queries that has a derived columns like our example for `PostSearch`'s `author_full_name`.
Sometimes we need to sort our query results by this column.

```php
// CAUTION:
// Remember to wrap column with MySQL functions with DB::raw() in column definition
Post::search('Some search')->orderBy(Post::searchQuery()->author_full_name, 'desc')->paginate();
Post::search('Some search')->where(Post::searchQuery()->author_full_name, 'William%')->paginate();
```

### Running gridQuery and searchQuery on its own

You can run gridQuery and searchQuery on its own but you need to make sure you initiliaze your query.

```php
use AjCastro\Searchable\BaseSearchQuery;

class PostSearch extends BaseSearchQuery
{
    public function query()
    {
        // Initialize query when $this->query is not available.
        $query = $this->query ?? Post::query();
        return $this->query;
        // ->leftJoin('authors', 'authors.id', '=', 'posts.author_id')
        // -> ... and so on
    }
}

// Then you can run it...
(new PostSearch)->search('something')->paginate();
```

### Grid Query Declarative Definition

```php
use AjCastro\Searchable\BaseGridQuery;

class PostGridQuery extends BaseGridQuery
{
    public function initQuery()
    {
        return Post::leftJoin('authors', 'authors.id', '=', 'posts.author_id');
    }

    public function columns()
    {
        return [
            'posts.title', // same with 'title' => 'posts.title'
            'text' => 'posts.body', // will result to "posts.body as text"
            'author_full_name' => 'CONCAT(authors.first_name, " ", authors.last_name)'
        ];
    }
}
```

```php
$gridQuery = new PostGridQuery;
$actualColumn = $gridQuery->getColumn('author_full_name');
$actualColumn = $gridQuery->author_full_name; // or using magic getters
$gridQuery
    ->selectColumns() // puts columns() to $query->select() and return the laravel query builder
    ->orderBy($actualColumn, 'desc')
    ->get();
```

### Search Query Declarative Definition

```php
use AjCastro\Searchable\BaseSearchQuery;

class PostSearch extends BaseSearchQuery
{
    public function query()
    {
        // $this->query is available since this is set on Searchable trait scopeSearch() method
        // If you're going to run this searchQuery on its own and not via scopeSearch()
        // you should consider to initialize $this->query first or use initQuery() method instead of query()
        // just like the above example
        return $this->query->leftJoin('authors', 'authors.id', '=', 'posts.author_id');
    }

    public function columns()
    {
        return [
            'posts.title', // same with 'title' => 'posts.title'
            'text' => 'posts.body', // will result to "posts.body as text"
            'author_full_name' => 'CONCAT(authors.first_name, " ", authors.last_name)'
        ];
    }
}
```

```php
// All defined columns are searchable in the query
$searchQuery = new PostSearch;
$searchQuery->search('This is a post title.');
$searchQuery->search('This is a post body.');
$searchQuery->search('William Shakespeare');
// You can chain laravel query builder's paginate() or get() afterwards
$searchQuery->search('William Shakespeare')->get();
// If you want to select the columns from the columns() we call selectColumns(), use initQuery for this
$results = tap($searchQuery)->search('William Shakespeare')->selectColumns()->get();
$results = [
    [
        'title' => 'This is a post title',
        'text' => 'This is a post body.',
        'author_full_name' => 'William Shakespeare'
    ],
    // ... and so on
];
```

## Credits

- Ray Anthony Madrona [@raymadrona](https://github.com/raymadrona), for the sort relevance using MySQL `LOCATE()`.
- [nicolaslopezj/searchable](https://github.com/nicolaslopezj/searchable), for the `$searchable` property declaration style.
