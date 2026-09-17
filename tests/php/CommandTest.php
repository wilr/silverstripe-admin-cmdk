<?php

namespace Wilr\AdminCmdk\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;
use Wilr\AdminCmdk\Model\Command;

class CommandTest extends SapphireTest
{
    protected $usesDatabase = true;

    public function testFluentSetters()
    {
        $command = Command::create('new-page')
            ->setLabel('Create a new page')
            ->setDescription('Adds a new page to the site tree')
            ->setIcon('font-icon-plus-circled')
            ->setKeywords(['add', 'page'])
            ->setLink('admin/pages/add')
            ->setShortcut('G then P');

        $this->assertSame('new-page', $command->getCode());
        $this->assertSame('Create a new page', $command->getLabel());
        $this->assertSame('Adds a new page to the site tree', $command->getDescription());
        $this->assertSame('font-icon-plus-circled', $command->getIcon());
        $this->assertSame(['add', 'page'], $command->getKeywords());
        $this->assertSame('admin/pages/add', $command->getLink());
        $this->assertSame('G then P', $command->getShortcut());
    }

    public function testNoPermissionMeansVisibleToAnyone()
    {
        $command = Command::create('open-pages');

        $this->assertTrue($command->canView(null));
        $this->assertTrue($command->canView($this->createMemberWithPermission('CMS_ACCESS_CMSMain')));
    }

    public function testStringPermissionIsChecked()
    {
        $command = Command::create('open-security')->setPermission('ADMIN');

        $admin = $this->createMemberWithPermission('ADMIN');
        $editor = $this->createMemberWithPermission('CMS_ACCESS_CMSMain');

        $this->assertTrue($command->canView($admin));
        $this->assertFalse($command->canView($editor));
        $this->assertFalse($command->canView(null));
    }

    public function testArrayPermissionIsAnyMatch()
    {
        $command = Command::create('open-either')->setPermission(['CMS_ACCESS_CMSMain', 'CMS_ACCESS_AssetAdmin']);

        $pagesEditor = $this->createMemberWithPermission('CMS_ACCESS_CMSMain');
        $unrelated = $this->createMemberWithPermission('CMS_ACCESS_ReportAdmin');

        $this->assertTrue($command->canView($pagesEditor));
        $this->assertFalse($command->canView($unrelated));
    }

    public function testClosurePermission()
    {
        $command = Command::create('custom-check')->setPermission(
            fn (?Member $member) => $member && $member->FirstName === 'Allowed'
        );

        $allowed = new Member(['FirstName' => 'Allowed']);
        $denied = new Member(['FirstName' => 'Denied']);

        $this->assertTrue($command->canView($allowed));
        $this->assertFalse($command->canView($denied));
        $this->assertFalse($command->canView(null));
    }

    public function testToArrayDoesNotLeakPermission()
    {
        $command = Command::create('open-security')
            ->setLabel('Open Security')
            ->setPermission('ADMIN');

        $this->assertArrayNotHasKey('permission', $command->toArray());
    }
}
