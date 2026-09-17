# Search providers

As the user types into the palette (2+ characters, debounced), the frontend calls
`GET admin/cmdk/search?q=...`, which resolves a `Wilr\AdminCmdk\Search\CommandSearchProvider`
via `Injector` and calls `search()` on it.

```php
interface CommandSearchProvider
{
    /**
     * @return SearchResult[]
     */
    public function search(string $query, ?Member $member): array;
}
```

**Implementations are responsible for their own permission filtering.** A result returned from
`search()` is trusted by the controller and sent to the browser as-is - there is no additional
filtering step afterwards. This mirrors how `Command::setPermission()` works: the check happens
before data leaves the server, not after.

## The default: `DatabaseSearchProvider`

Does a `PartialMatch` lookup across a configurable list of `DataObject` classes/fields, filtering
every candidate through `canView()`:

```yaml
Wilr\AdminCmdk\Search\DatabaseSearchProvider:
  result_limit: 20          # Total results returned, across all classes.
  per_class_limit: 5        # Results returned per configured class.
  minimum_query_length: 2   # Queries shorter than this return no results.
  searchable_classes:
    SilverStripe\CMS\Model\SiteTree:
      fields: ['Title', 'MenuTitle']
      icon: 'font-icon-p-file'
      group: 'Pages'
    SilverStripe\Assets\File:
      fields: ['Name', 'Title']
      icon: 'font-icon-image'
      group: 'Files'
    # Add your own DataObjects - no PHP required.
    App\Model\Product:
      fields: ['Title', 'SKU']
      icon: 'font-icon-box'
      group: 'Products'
```

For each configured class, results are ranked by whatever order `DataList::filterAny()` returns
them in, then trimmed to `per_class_limit` **after** filtering out records the searching member
can't view via `canView()`. This means the provider over-fetches candidates internally to avoid
running out of results after permission filtering (see `DatabaseSearchProvider::searchClass()` if
you need to tune that behaviour further by subclassing).

## Writing a custom provider

Implement the interface, then bind it via `Injector`:

```php
namespace App\Search;

use SilverStripe\Security\Member;
use Wilr\AdminCmdk\Search\CommandSearchProvider;
use Wilr\AdminCmdk\Search\SearchResult;

class AlgoliaSearchProvider implements CommandSearchProvider
{
    public function search(string $query, ?Member $member): array
    {
        $hits = $this->getAlgoliaClient()
            ->initIndex('pages')
            ->search($query, ['hitsPerPage' => 10]);

        $results = [];

        foreach ($hits['hits'] as $hit) {
            $page = \SilverStripe\CMS\Model\SiteTree::get()->byID($hit['objectID']);

            // Still required - Algolia doesn't know about SilverStripe permissions.
            if (!$page || !$page->canView($member)) {
                continue;
            }

            $results[] = SearchResult::create(
                $page->Title,
                $page->CMSEditLink(),
                $page->Parent()->Title,
                'font-icon-p-file',
                'Pages'
            );
        }

        return $results;
    }

    private function getAlgoliaClient()
    {
        // ...
    }
}
```

```yaml
SilverStripe\Core\Injector\Injector:
  Wilr\AdminCmdk\Search\CommandSearchProvider:
    class: App\Search\AlgoliaSearchProvider
```

## `SearchResult`

The DTO returned from `search()`:

```php
SearchResult::create(
    string $title,
    string $link,
    string $description = '',
    string $icon = 'font-icon-search',
    string $group = 'Search results'
);
```

`$group` controls which `Command.Group` heading the result appears under in the palette - results
from different providers/classes with the same `$group` value are merged together.
