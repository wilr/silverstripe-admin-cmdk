<?php

namespace Wilr\AdminCmdk\Tests;

use SilverStripe\Dev\SapphireTest;
use Wilr\AdminCmdk\CommandPalette;
use Wilr\AdminCmdk\Model\Command;

class CommandPaletteTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected function setUp(): void
    {
        parent::setUp();
        CommandPalette::reset();
    }

    protected function tearDown(): void
    {
        CommandPalette::reset();
        parent::tearDown();
    }

    public function testGroupIsCreatedOnFirstAccess()
    {
        $this->assertFalse(CommandPalette::hasGroup('content'));

        $group = CommandPalette::group('content', 'Content');

        $this->assertTrue(CommandPalette::hasGroup('content'));
        $this->assertSame('Content', $group->getTitle());
    }

    public function testGroupIsReusedOnSecondAccess()
    {
        $first = CommandPalette::group('content', 'Content');
        $first->addCommand(Command::create('a'));

        $second = CommandPalette::group('content');

        $this->assertSame($first, $second);
        $this->assertCount(1, $second->getCommands());
    }

    public function testGroupsAreOrderedByPriorityDescending()
    {
        CommandPalette::group('low', 'Low', 1);
        CommandPalette::group('high', 'High', 10);
        CommandPalette::group('medium', 'Medium', 5);

        $codes = array_map(fn ($group) => $group->getCode(), CommandPalette::getGroups());

        $this->assertSame(['high', 'medium', 'low'], $codes);
    }

    public function testRemoveGroup()
    {
        CommandPalette::group('content', 'Content');
        CommandPalette::removeGroup('content');

        $this->assertFalse(CommandPalette::hasGroup('content'));
    }

    public function testGetViewableGroupsAsArrayOmitsEmptyGroups()
    {
        CommandPalette::group('content', 'Content')
            ->addCommand(Command::create('open')->setPermission('CMS_ACCESS_CMSMain'));

        CommandPalette::group('admin-only', 'Admin only')
            ->addCommand(Command::create('secret')->setPermission('ADMIN'));

        $editor = $this->createMemberWithPermission('CMS_ACCESS_CMSMain');
        $groups = CommandPalette::getViewableGroupsAsArray($editor);

        $this->assertCount(1, $groups);
        $this->assertSame('content', $groups[0]['code']);
    }
}
