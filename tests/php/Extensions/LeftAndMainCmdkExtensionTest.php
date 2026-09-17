<?php

namespace Wilr\AdminCmdk\Tests\Extensions;

use SilverStripe\Admin\CMSMenu;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\FunctionalTest;
use Wilr\AdminCmdk\Extensions\LeftAndMainCmdkExtension;

class LeftAndMainCmdkExtensionTest extends FunctionalTest
{
    protected $usesDatabase = true;

    protected function tearDown(): void
    {
        CMSMenu::remove_menu_item('CommandPalette');
        parent::tearDown();
    }

    public function testTriggerIsAddedForUserWithCmsAccess()
    {
        Config::modify()->set(LeftAndMainCmdkExtension::class, 'enabled', true);
        $this->logInWithPermission('CMS_ACCESS_CMSMain');

        $response = $this->get('admin/pages');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('data-cmdk-trigger', $response->getBody());
    }

    public function testTriggerIsNotAddedWhenDisabled()
    {
        Config::modify()->set(LeftAndMainCmdkExtension::class, 'enabled', false);
        $this->logInWithPermission('CMS_ACCESS_CMSMain');

        $response = $this->get('admin/pages');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringNotContainsString('data-cmdk-trigger', $response->getBody());
    }
}
