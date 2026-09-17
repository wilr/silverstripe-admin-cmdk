<?php

namespace Wilr\AdminCmdk;

use SilverStripe\Security\Member;
use Wilr\AdminCmdk\Model\Command;
use Wilr\AdminCmdk\Model\CommandGroup;

/**
 * Static facade for registering command palette groups/commands, intended to be called from a
 * project's `_config.php` (mirrors the way `SilverStripe\Admin\CMSMenu` is configured):
 *
 * ```php
 * use Wilr\AdminCmdk\CommandPalette;
 * use Wilr\AdminCmdk\Model\Command;
 *
 * CommandPalette::group('content', 'Content')
 *     ->addCommand(
 *         Command::create('new-page')
 *             ->setLabel('Create a new page')
 *             ->setLink('admin/pages/add')
 *             ->setPermission('CMS_ACCESS_CMSMain')
 *     );
 * ```
 *
 * The registry is rebuilt on every request (each `_config.php` runs per-request), so there is no
 * persistence concern - it behaves like an in-memory config object for the lifetime of the request.
 */
class CommandPalette
{
    /** @var array<string, CommandGroup> */
    private static array $groups = [];

    /**
     * Fetch (creating if necessary) the group with the given code.
     */
    public static function group(string $code, ?string $title = null, int $priority = 0): CommandGroup
    {
        if (!isset(self::$groups[$code])) {
            self::$groups[$code] = CommandGroup::create($code, $title ?? $code, $priority);
        } elseif ($title !== null) {
            self::$groups[$code]->setTitle($title);
        }

        return self::$groups[$code];
    }

    public static function hasGroup(string $code): bool
    {
        return isset(self::$groups[$code]);
    }

    public static function removeGroup(string $code): void
    {
        unset(self::$groups[$code]);
    }

    /**
     * @return CommandGroup[] Groups ordered by priority (desc), then registration order.
     */
    public static function getGroups(): array
    {
        $groups = array_values(self::$groups);

        usort($groups, fn (CommandGroup $a, CommandGroup $b) => $b->getPriority() <=> $a->getPriority());

        return $groups;
    }

    /**
     * All groups (and their commands) serialised for the given member, with commands the member
     * cannot view removed, and empty groups omitted entirely.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getViewableGroupsAsArray(?Member $member): array
    {
        $result = [];

        foreach (self::getGroups() as $group) {
            $data = $group->toArray($member);

            if (!empty($data['commands'])) {
                $result[] = $data;
            }
        }

        return $result;
    }

    /**
     * Removes all registered groups/commands. Intended for use in tests.
     */
    public static function reset(): void
    {
        self::$groups = [];
    }
}
