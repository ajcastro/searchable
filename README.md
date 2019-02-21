## SEDP-MIS | BaseGridQuery

### Grid Query Declarative Definition

Helpful for complex table queries with multiple joins and derived columns.

```php
use SedpMis\BaseGridQuery\BaseGridQuery;

class PostGridQuery extends BaseGridQuery
{
    public function initQuery() {
        return Post::leftJoin('authors', 'authors.id', '=', 'posts.author_id');
    }

    public function columns() {
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
    public function initQuery() {
        return Post::leftJoin('authors', 'authors.id', '=', 'posts.author_id');
    }

    public function columns() {
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
TODO

### Searchable Model Custom Search Query
TODO
