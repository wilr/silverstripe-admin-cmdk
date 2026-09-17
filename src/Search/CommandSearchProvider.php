<?php

namespace Wilr\AdminCmdk\Search;

use SilverStripe\Security\Member;

/**
 * Resolves a free-text query typed into the command palette into a list of {@see SearchResult}s.
 *
 * The default implementation ({@see DatabaseSearchProvider}) does a basic ORM lookup. Projects can
 * swap in a different backend (e.g. Algolia, Elasticsearch) by rebinding this interface via
 * `Injector`:
 *
 * ```yaml
 * SilverStripe\Core\Injector\Injector:
 *   Wilr\AdminCmdk\Search\CommandSearchProvider:
 *     class: MyProject\Search\AlgoliaSearchProvider
 * ```
 *
 * Implementations are responsible for their own permission filtering - a result returned from
 * `search()` is trusted by the controller and sent to the browser as-is.
 */
interface CommandSearchProvider
{
    /**
     * @param string $query The raw, trimmed search term typed by the user.
     * @param Member|null $member The member performing the search - implementations must only
     *   return results this member is permitted to view.
     * @return SearchResult[]
     */
    public function search(string $query, ?Member $member): array;
}
