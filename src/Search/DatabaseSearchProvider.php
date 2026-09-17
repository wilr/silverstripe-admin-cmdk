<?php

namespace Wilr\AdminCmdk\Search;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Security\Member;

/**
 * Default {@see CommandSearchProvider}: does a simple `PartialMatch` lookup across a
 * project-configurable list of `DataObject` classes/fields, filtering every candidate through
 * `canView()` before it is returned.
 *
 * Configure additional searchable classes via YAML, no PHP required:
 *
 * ```yaml
 * Wilr\AdminCmdk\Search\DatabaseSearchProvider:
 *   searchable_classes:
 *     App\Model\Product:
 *       fields: ['Title', 'SKU']
 *       icon: 'font-icon-box'
 *       group: 'Products'
 * ```
 */
class DatabaseSearchProvider implements CommandSearchProvider
{
    use Injectable;
    use Configurable;

    /**
     * Map of DataObject class => ['fields' => [...], 'icon' => '...', 'group' => '...'].
     *
     * @var array<string, array<string, mixed>>
     */
    private static array $searchable_classes = [];

    /**
     * Maximum number of results returned in total, across all searchable classes.
     */
    private static int $result_limit = 20;

    /**
     * Maximum number of results returned per searchable class.
     */
    private static int $per_class_limit = 5;

    /**
     * Queries shorter than this are ignored (an empty result set is returned).
     */
    private static int $minimum_query_length = 2;

    public function search(string $query, ?Member $member): array
    {
        $query = trim($query);

        if (mb_strlen($query) < static::config()->get('minimum_query_length')) {
            return [];
        }

        $results = [];
        $resultLimit = (int) static::config()->get('result_limit');
        $perClassLimit = (int) static::config()->get('per_class_limit');

        foreach (static::config()->get('searchable_classes') ?? [] as $class => $spec) {
            if (count($results) >= $resultLimit) {
                break;
            }

            if (!class_exists($class)) {
                continue;
            }

            $fields = $spec['fields'] ?? [];

            if (empty($fields)) {
                continue;
            }

            $found = $this->searchClass($class, $fields, $query, $member, $perClassLimit);

            $icon = $spec['icon'] ?? 'font-icon-search';
            $group = $spec['group'] ?? $class;

            foreach ($found as $record) {
                if (count($results) >= $resultLimit) {
                    break;
                }

                $results[] = SearchResult::create(
                    $this->getRecordTitle($record, $fields),
                    $this->getRecordLink($record),
                    $this->getRecordDescription($record),
                    $icon,
                    $group
                );
            }
        }

        return $results;
    }

    /**
     * @param string[] $fields
     * @return \SilverStripe\ORM\DataObject[]
     */
    protected function searchClass(string $class, array $fields, string $query, ?Member $member, int $limit): array
    {
        $filters = [];

        foreach ($fields as $field) {
            $filters["{$field}:PartialMatch"] = $query;
        }

        // Over-fetch candidates since some will be dropped by canView() below.
        $candidates = $class::get()->filterAny($filters)->limit($limit * 4);

        $matches = [];

        foreach ($candidates as $record) {
            if (count($matches) >= $limit) {
                break;
            }

            if (!$record->hasMethod('canView') || $record->canView($member)) {
                $matches[] = $record;
            }
        }

        return $matches;
    }

    /**
     * @param string[] $fields
     */
    protected function getRecordTitle($record, array $fields): string
    {
        if ($record->hasMethod('getTitle') && $record->getTitle()) {
            return (string) $record->getTitle();
        }

        $primaryField = $fields[0] ?? null;

        if ($primaryField && isset($record->$primaryField)) {
            return (string) $record->$primaryField;
        }

        return (string) $record->ID;
    }

    protected function getRecordDescription($record): string
    {
        if ($record->hasMethod('getParent') && $record->getParent() && $record->getParent()->exists()) {
            return (string) $record->getParent()->Title;
        }

        return '';
    }

    protected function getRecordLink($record): string
    {
        if ($record->hasMethod('CMSEditLink')) {
            return (string) $record->CMSEditLink();
        }

        return '';
    }
}
