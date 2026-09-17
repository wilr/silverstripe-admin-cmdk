<?php

// Default commands, registered the same way a project would register its own - so the module is
// useful out of the box with zero project configuration. Projects can add to, or remove.
// (via `CommandPalette::removeGroup()`) any of these.

use Wilr\AdminCmdk\CommandPalette;
use Wilr\AdminCmdk\Model\Command;

CommandPalette::group('wilr-cmdk-navigate', 'Go to', 5)
    ->addCommand(
        Command::create('wilr-cmdk-pages')
            ->setLabel('Pages')
            ->setIcon('font-icon-sitemap')
            ->setKeywords(['content', 'site tree'])
            ->setLink('admin/pages')
            ->setPermission('CMS_ACCESS_CMSMain')
    )
    ->addCommand(
        Command::create('wilr-cmdk-files')
            ->setLabel('Files')
            ->setIcon('font-icon-image')
            ->setKeywords(['assets', 'images', 'documents'])
            ->setLink('admin/assets')
            ->setPermission('CMS_ACCESS_AssetAdmin')
    )
    ->addCommand(
        Command::create('wilr-cmdk-settings')
            ->setLabel('Settings')
            ->setIcon('font-icon-cog')
            ->setKeywords(['sitename', 'theme', 'config'])
            ->setLink('admin/settings')
            ->setPermission('CMS_ACCESS_SilverStripe\\SiteConfig\\SiteConfigLeftAndMain')
    )
    ->addCommand(
        Command::create('wilr-cmdk-security')
            ->setLabel('Users & permissions')
            ->setIcon('font-icon-torsos-all')
            ->setKeywords(['members', 'groups', 'roles', 'security'])
            ->setLink('admin/security')
            ->setPermission('CMS_ACCESS_SilverStripe\\Admin\\SecurityAdmin')
    );
