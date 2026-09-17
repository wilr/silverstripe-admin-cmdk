<?php

namespace Wilr\AdminCmdk\Model;

use Closure;
use InvalidArgumentException;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

/**
 * A single actionable entry in the command palette, e.g. "Create a new page".
 *
 * Commands are registered from PHP (typically a project's `_config.php`) via the
 * {@see \Wilr\AdminCmdk\CommandPalette} facade, and are serialised to JSON for the frontend once
 * their permission has been checked against the current member.
 */
class Command
{
    use Injectable;

    private string $code;

    private string $label;

    private string $description = '';

    private string $icon = 'font-icon-search';

    /** @var string[] */
    private array $keywords = [];

    private string $link = '';

    private string $shortcut = '';

    /** @var string|string[]|Closure|null */
    private $permission = null;

    public static function create(string $code): static
    {
        return new static($code);
    }

    public function __construct(string $code)
    {
        if ($code === '') {
            throw new InvalidArgumentException('Command code cannot be empty');
        }

        $this->code = $code;
        $this->label = $code;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setIcon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * @param string[] $keywords
     */
    public function setKeywords(array $keywords): static
    {
        $this->keywords = array_values($keywords);

        return $this;
    }

    /**
     * @return string[]
     */
    public function getKeywords(): array
    {
        return $this->keywords;
    }

    public function setLink(string $link): static
    {
        $this->link = $link;

        return $this;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    /**
     * A human-readable keyboard shortcut hint shown against the item, e.g. "G then P".
     * Purely cosmetic - it is not bound automatically.
     */
    public function setShortcut(string $shortcut): static
    {
        $this->shortcut = $shortcut;

        return $this;
    }

    public function getShortcut(): string
    {
        return $this->shortcut;
    }

    /**
     * @param string|string[]|Closure|null $permission A permission code, an array of permission
     *   codes (any of which grants access), or a `Closure(Member $member): bool` for custom logic.
     */
    public function setPermission($permission): static
    {
        if ($permission !== null && !is_string($permission) && !is_array($permission) && !($permission instanceof Closure)) {
            throw new InvalidArgumentException(
                'Permission must be a string, an array of strings, a Closure, or null'
            );
        }

        $this->permission = $permission;

        return $this;
    }

    /**
     * @return string|string[]|Closure|null
     */
    public function getPermission()
    {
        return $this->permission;
    }

    /**
     * Whether the given member is allowed to see this command. A command with no permission set
     * is visible to any authenticated CMS user.
     */
    public function canView(?Member $member): bool
    {
        if ($this->permission === null) {
            return true;
        }

        if ($this->permission instanceof Closure) {
            return (bool) ($this->permission)($member);
        }

        // Explicitly guard against Permission::checkMember()'s own null-member handling, which
        // falls back to the current session's logged-in user - a null $member here must mean
        // "no one", not "whoever happens to be logged in".
        if ($member === null) {
            return false;
        }

        return (bool) Permission::checkMember($member, $this->permission);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'label' => $this->label,
            'description' => $this->description,
            'icon' => $this->icon,
            'keywords' => $this->keywords,
            'link' => $this->link,
            'shortcut' => $this->shortcut,
        ];
    }
}
