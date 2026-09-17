<?php

namespace Wilr\AdminCmdk\Controllers;

use SilverStripe\Admin\AdminController;
use SilverStripe\Admin\CMSMenu;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use Wilr\AdminCmdk\CommandPalette;
use Wilr\AdminCmdk\Search\CommandSearchProvider;

/**
 * JSON API backing the frontend command palette.
 *
 * Extends `AdminController` (not `LeftAndMain`) so it is automatically routed under `/admin/cmdk/*`
 * by `AdminRootController` via its `url_segment`, without being added to the CMS menu itself -
 * `CMSMenu` only scans `LeftAndMain` subclasses for menu items, so this stays an API endpoint rather
 * than becoming a visible CMS section.
 *
 * - `GET admin/cmdk/commands` - registered {@see CommandPalette} groups/commands, filtered by
 *   permission for the current member.
 * - `GET admin/cmdk/search?q=` - live results from the configured {@see CommandSearchProvider}.
 */
class CommandPaletteController extends AdminController
{
    private static string $url_segment = 'cmdk';

    private static array $allowed_actions = [
        'commands',
        'search',
    ];

    /**
     * Overrides `AdminController::canView()`'s default "has the specific CMS_ACCESS_<class>
     * permission" check - this is an API shared by every CMS section, so access should match
     * "has access to at least one CMS section" instead, the same rule `CMSMenu` itself uses to
     * decide what to show in the menu.
     */
    public function canView($member = null): bool
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }

        return $member instanceof Member && !empty(CMSMenu::get_viewable_menu_items($member));
    }

    public function commands(HTTPRequest $request): HTTPResponse
    {
        $groups = CommandPalette::getViewableGroupsAsArray(Security::getCurrentUser());

        return $this->jsonSuccess(200, ['groups' => $groups]);
    }

    public function search(HTTPRequest $request): HTTPResponse
    {
        $query = trim((string) $request->getVar('q'));

        if ($query === '') {
            return $this->jsonSuccess(200, ['results' => []]);
        }

        $provider = Injector::inst()->get(CommandSearchProvider::class);
        $results = $provider->search($query, Security::getCurrentUser());

        return $this->jsonSuccess(200, [
            'results' => array_map(fn ($result) => $result->toArray(), $results),
        ]);
    }
}
