# Configuration

Commands are registered from PHP, in the same spirit as `SilverStripe\Admin\CMSMenu` - typically
from your project's `app/_config.php`, or from a module's own `_config.php`/`_config/config.yml`
plus a bootstrap class.

## `CommandPalette`

A static facade over an in-memory registry of `CommandGroup`s, rebuilt on every request (there is
nothing to persist - `_config.php` files run on every request already).

```php
use Wilr\AdminCmdk\CommandPalette;

// Get or create a group. Safe to call multiple times - the same group is reused, so a project
// and another module can both contribute commands to e.g. a "content" group.
CommandPalette::group(string $code, ?string $title = null, int $priority = 0): CommandGroup

CommandPalette::hasGroup(string $code): bool
CommandPalette::removeGroup(string $code): void

// All registered groups, ordered by priority (highest first).
CommandPalette::getGroups(): CommandGroup[]
```

## `CommandGroup`

A named, ordered collection of commands, rendered as one `Command.Group` in the palette.

```php
use Wilr\AdminCmdk\Model\CommandGroup;

CommandGroup::create(string $code, string $title, int $priority = 0)
    ->setTitle(string $title)
    ->setPriority(int $priority)
    ->addCommand(Command $command)
    ->removeCommand(string $code);
```

Groups with a higher `$priority` are shown first. The built-in groups shipped by this module use
priorities in the `0`-`10` range - use a higher number to appear above them, or negative to sink
below.

## `Command`

A single actionable entry.

```php
use Wilr\AdminCmdk\Model\Command;

Command::create('new-page')
    ->setLabel('Create a new page')          // Required - shown in the list.
    ->setDescription('...')                  // Optional, currently unused by the default UI.
    ->setIcon('font-icon-plus-circled')       // Optional. Any silverstripe/admin icon font class.
    ->setKeywords(['add', 'page'])            // Optional. Matched in addition to the label.
    ->setLink('admin/pages/add')              // Where the browser navigates to on selection.
    ->setShortcut('G then P')                 // Optional, cosmetic only - not bound automatically.
    ->setPermission('CMS_ACCESS_CMSMain');    // See below.
```

### Permissions

`setPermission()` accepts:

- A **permission code** string, e.g. `'CMS_ACCESS_CMSMain'`.
- An **array of codes**, matched with "any of these" semantics (`Permission::checkMember()`'s
  default behaviour).
- A **Closure** for anything more specific than a flat permission check, e.g. checking a
  particular record:

  ```php
  Command::create('edit-homepage')
      ->setLabel('Edit the homepage')
      ->setLink('admin/pages/edit/show/' . $homepageID)
      ->setPermission(function (?\SilverStripe\Security\Member $member) use ($homepageID) {
          $page = \SilverStripe\CMS\Model\SiteTree::get()->byID($homepageID);
          return $page && $page->canEdit($member);
      });
  ```
- `null` (the default) - visible to any authenticated CMS user.

Permission checks happen **server-side**, in `Wilr\AdminCmdk\Controllers\CommandPaletteController`,
before a command is ever serialised into the JSON response the browser receives. A command a
member can't see is not sent to the browser at all - it isn't merely hidden by client-side CSS or
JS. Groups that end up with zero viewable commands after filtering are omitted from the response
entirely.

## Enabling/disabling the CMS menu trigger

```yaml
Wilr\AdminCmdk\Extensions\LeftAndMainCmdkExtension:
  enabled: false # defaults to true
```

The `Cmd/Ctrl+K` keyboard shortcut is only wired up when the frontend bundle loads, which only
happens when this extension decides to include it - so disabling the extension disables the
keyboard shortcut too, not just the menu icon.
