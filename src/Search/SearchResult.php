<?php

namespace Wilr\AdminCmdk\Search;

use JsonSerializable;
use SilverStripe\Core\Injector\Injectable;

/**
 * A single search hit returned by a {@see CommandSearchProvider}, serialised to the frontend
 * exactly like a static {@see \Wilr\AdminCmdk\Model\Command}, just sourced dynamically.
 */
class SearchResult implements JsonSerializable
{
    use Injectable;

    private string $title;

    private string $description;

    private string $link;

    private string $icon;

    private string $group;

    public static function create(
        string $title,
        string $link,
        string $description = '',
        string $icon = 'font-icon-search',
        string $group = 'Search results'
    ): static {
        return new static($title, $link, $description, $icon, $group);
    }

    public function __construct(
        string $title,
        string $link,
        string $description = '',
        string $icon = 'font-icon-search',
        string $group = 'Search results'
    ) {
        $this->title = $title;
        $this->link = $link;
        $this->description = $description;
        $this->icon = $icon;
        $this->group = $group;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'link' => $this->link,
            'icon' => $this->icon,
            'group' => $this->group,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
