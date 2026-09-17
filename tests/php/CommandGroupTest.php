<?php

namespace Wilr\AdminCmdk\Tests;

use SilverStripe\Dev\SapphireTest;
use Wilr\AdminCmdk\Model\Command;
use Wilr\AdminCmdk\Model\CommandGroup;

class CommandGroupTest extends SapphireTest
{
    protected $usesDatabase = true;

    public function testAddAndGetCommands()
    {
        $group = CommandGroup::create('content', 'Content');
        $group->addCommand(Command::create('a'));
        $group->addCommand(Command::create('b'));

        $codes = array_map(fn (Command $command) => $command->getCode(), $group->getCommands());

        $this->assertSame(['a', 'b'], $codes);
        $this->assertNotNull($group->getCommand('a'));
        $this->assertNull($group->getCommand('missing'));
    }

    public function testRemoveCommand()
    {
        $group = CommandGroup::create('content', 'Content');
        $group->addCommand(Command::create('a'));
        $group->removeCommand('a');

        $this->assertNull($group->getCommand('a'));
        $this->assertCount(0, $group->getCommands());
    }

    public function testGetViewableCommandsFiltersByPermission()
    {
        $group = CommandGroup::create('content', 'Content');
        $group->addCommand(Command::create('open')->setPermission('CMS_ACCESS_CMSMain'));
        $group->addCommand(Command::create('secret')->setPermission('ADMIN'));

        $editor = $this->createMemberWithPermission('CMS_ACCESS_CMSMain');
        $viewable = $group->getViewableCommands($editor);
        $codes = array_map(fn (Command $command) => $command->getCode(), $viewable);

        $this->assertSame(['open'], $codes);
    }

    public function testToArrayOmitsUnviewableCommands()
    {
        $group = CommandGroup::create('content', 'Content', 5);
        $group->addCommand(Command::create('open')->setLabel('Open')->setPermission('CMS_ACCESS_CMSMain'));
        $group->addCommand(Command::create('secret')->setLabel('Secret')->setPermission('ADMIN'));

        $editor = $this->createMemberWithPermission('CMS_ACCESS_CMSMain');
        $data = $group->toArray($editor);

        $this->assertSame('content', $data['code']);
        $this->assertSame('Content', $data['title']);
        $this->assertSame(5, $data['priority']);
        $this->assertCount(1, $data['commands']);
        $this->assertSame('open', $data['commands'][0]['code']);
    }
}
