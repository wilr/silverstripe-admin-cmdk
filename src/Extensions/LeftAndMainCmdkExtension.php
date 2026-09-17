<?php

namespace Wilr\AdminCmdk\Extensions;

use SilverStripe\Admin\CMSMenu;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extension;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;
use Wilr\AdminCmdk\Controllers\CommandPaletteController;

/**
 * Applied to `SilverStripe\Admin\LeftAndMain`. Adds the command palette trigger to the CMS menu
 * and loads the frontend bundle, for any member with access to at least one CMS section.
 *
 * Disable entirely via YAML:
 *
 * ```yaml
 * Wilr\AdminCmdk\Extensions\LeftAndMainCmdkExtension:
 *   enabled: false
 * ```
 */
class LeftAndMainCmdkExtension extends Extension
{
    use Configurable;

    private static bool $enabled = true;

    /**
     * Hooked in via `Controller::doInit()`'s `onAfterInit` extension point (not `init()` itself -
     * `Extension::init()` is never invoked automatically for controllers, only `onBeforeInit()`/
     * `onAfterInit()` are).
     */
    public function onAfterInit()
    {
        if (!static::config()->get('enabled')) {
            return;
        }

        $member = Security::getCurrentUser();

        if (!$member || !CMSMenu::get_viewable_menu_items($member)) {
            return;
        }

        Requirements::javascript('wilr/silverstripe-admin-cmdk: client/dist/js/bundle.js');
        Requirements::css('wilr/silverstripe-admin-cmdk: client/dist/styles/bundle.css');

        $controller = CommandPaletteController::create();

        Requirements::customScript(
            'window.__CmdkConfig__ = ' . json_encode([
                'endpoints' => [
                    'commands' => $controller->Link('commands'),
                    'search' => $controller->Link('search'),
                ],
            ]),
            'wilr-admin-cmdk-config'
        );

        CMSMenu::add_link(
            'CommandPalette',
            _t(__CLASS__ . '.MENU_TITLE', 'Search'),
            '#',
            -1,
            ['data-cmdk-trigger' => 'true'],
            'font-icon-search'
        );
    }
}
