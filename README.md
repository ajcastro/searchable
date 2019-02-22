## SEDP-MIS | BaseGridQuery

Full-text search and re-usable queries in laravel.

### Grid Query Declarative Definition

- Helpful for complex table queries with multiple joins and derived columns.
- Re-usable queries and column definitions.

```php
use SedpMis\BaseGridQuery\BaseGridQuery;

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
            'text' => 'posts.body', // automatic alias of posts.body to text
            'author_full_name' => 'CONCAT(authors.first_name, ' ', authors.last_name)'
        ];
    }
}
```

Re-usable column definitions

```php
$gridQuery = new PostGridQuery;
$actualColumn = $gridQuery->getColumn('author_full_name');
$actualColumn = $gridQuery->author_full_name; // or using magic getters
$gridQuery->orderBy($actualColumn, 'desc');
```

### Search Query Declarative Definition

```php
use SedpMis\BaseGridQuery\BaseSearchQuery;

class PostSearch extends BaseSearchQuery
{
    public function query()
    {
        // $this->query is available since this is set on SearchableModel trait scopeSearch() method
        // If you're going to run this searchQuery on its own and not via scopeSearch()
        // you should consider to initialize $this->query first or use initQuery() method instead of query()
        // just like the above example
        return $this->query->leftJoin('authors', 'authors.id', '=', 'posts.author_id');
    }

    public function columns()
    {
        return [
            'posts.title', // same with 'title' => 'posts.title'
            'text' => 'posts.body', // automatic alias of posts.body to text
            'author_full_name' => 'CONCAT(authors.first_name, ' ', authors.last_name)'
        ];
    }
}

// Usage
// All defined columns are searchable in the query
$searchQuery = new PostSearch;
$searchQuery->search('This is a post title.');
$searchQuery->search('This is a post body.');
$searchQuery->search('William Shakespeare');
// You can chain laravel query builder's paginate() or get() afterwards
$results = $searchQuery->search('William Shakespeare')->get();
// Will output
$results = [
    [
        'title' => 'This is a post title',
        'text' => 'This is a post body.',
        'author_full_name' => 'William Shakespeare'
    ],
    // ... and so on
];
```

### Searchable Model

```php
use SedpMis\BaseGridQuery\SearchableModel;

class Post extends Model
{
    use SearchableModel;

    /**
     * Searchable columns of the model.
     * If this is not defined it will default to all table columns.
     */
    protected $searchableColumns = [
        'title',
        'body',
    ];
}

// Usage
// Call search anywhere
// This only search the columns available to the table of the model.
Post::search('Some post')->paginate();
Post::where('likes', '>', 100)->search('Some post')->paginate();
// If there are joins like if you want to include author's full_name use a custom search query.
```

### Searchable Model Custom Search Query

We can use the above example `PostSearch`.
We can use it as the default search query for the model like:

```php
class Post
{
    public function defaultSearchQuery()
    {
        return new PostSearch;
    }
}

// Usage
Post::search("We can now search for author's full_name like William Shakespeare")->paginate();
// This will return the models normal structure unlike if you're using the PostSearch which returns only the selected columns.
// We can do everything as usual like using with() to load relations
Post::with('authror')->search('William Shakespeare')->paginate();
```

We can also use custom search query temporarily by passing it as second parameter in `search()` method.

```php
Post::search('William Shakespeare', new PostSearch)->paginate();
```

### Using derived columns for order by and where conditions

Usually we have queries that has a derived columns like our example for `PostSearch`'s `author_full_name`.
Sometimes we need to sort our query results by this column.

```php
Post::search('Some search')->orderBy(Post::searchQuery()->author_full_name, 'desc')->paginate();
// This is equivalent to
Post::search('Some search')->orderBy('CONCAT(authrors.first_name, ' ', authors.last_name)', 'desc')->paginate();
```


### Bonus Code

Imagine we have an api for a table or list that has full-text searching and column sorting and pagination.
This is a usual setup for a table or list.
Our api call may look like this:

`
http://awesome-app.com/api/posts?per_page=10&page=1&sort_by=title&descending=true&search=SomePostTitle
`

Your code can look like this

```php
class PostsController
{
    public function index()
    {
        retur Post::search(request('search'))
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
