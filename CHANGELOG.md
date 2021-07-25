# Changelog

All notable changes to `ajcastro/searchable` will be documented in this file

## 2.0.0 (2021-07-25)

### Added
- Added `\AjCastro\Searchable\Columns`.
- Added `\AjCastro\Searchable\TableColumns`.
- Added `\AjCastro\Searchable\SearchParsers\*` classes.
- Added `\AjCastro\Searchable\BaseSearch`, simplified base search query decorator.
- Added `\AjCastro\Searchable\BaseSearch::parseUsing(callable $callback)` method using custom search string parsing.

### Changed
- Moved $searchable property methods to separate trait `\AjCastro\Searchable\WithSearchableColumns`.
- Improved implementation of `searchByRelevance()` scope query, it is not called by default and should be called explicitly.
- Change method `\AjCastro\Searchable\Searchable::isColumnValid($column)` to non-static/instance method.

### Removed
- Remove method `\AjCastro\Searchable\Searchable::getTableColumns()`.
