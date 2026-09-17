<?php

namespace Wilr\AdminCmdk\Tests;

use SilverStripe\Control\HTTPResponse;
use SilverStripe\Dev\FunctionalTest;
use Wilr\AdminCmdk\CommandPalette;
use Wilr\AdminCmdk\Model\Command;

/**
 * The core permission-coverage test for this module: proves that a command a member isn't
 * permitted to see never reaches the JSON response, and that groups with no viewable commands
 * left are dropped entirely rather than sent through empty.
 */
class CommandPaletteControllerTest extends FunctionalTest
{
    protected $usesDatabase = true;

    protected function setUp(): void
    {
        parent::setUp();
        CommandPalette::reset();

        CommandPalette::group('content', 'Content')
            ->addCommand(
                Command::create('open-pages')
                    ->setLabel('Open Pages')
                    ->setPermission('CMS_ACCESS_CMSMain')
            );

        CommandPalette::group('admin', 'Administration')
            ->addCommand(
                Command::create('open-security')
                    ->setLabel('Open Security')
                    ->setPermission('ADMIN')
            );
    }

    protected function tearDown(): void
    {
        CommandPalette::reset();
        parent::tearDown();
    }

    public function testAnonymousUsersAreDeniedAccess()
    {
        $this->logOut();

        $response = $this->getAjax('admin/cmdk/commands');

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testMemberWithoutAnyCmsAccessIsDenied()
    {
        // A member that exists, but is not assigned to any CMS_ACCESS_* permission at all.
        $member = $this->createMemberWithPermission('SOME_UNRELATED_PERMISSION');
        $this->logInAs($member);

        $response = $this->getAjax('admin/cmdk/commands');

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testEditorOnlySeesCommandsTheyHavePermissionFor()
    {
        $this->logInWithPermission('CMS_ACCESS_CMSMain');

        $response = $this->getAjax('admin/cmdk/commands');
        $this->assertSame(200, $response->getStatusCode());

        $codes = $this->getCommandCodes($response);

        $this->assertContains('open-pages', $codes);
        $this->assertNotContains('open-security', $codes);
    }

    public function testAdminSeesAllCommands()
    {
        $this->logInWithPermission('ADMIN');

        $response = $this->getAjax('admin/cmdk/commands');
        $codes = $this->getCommandCodes($response);

        $this->assertContains('open-pages', $codes);
        $this->assertContains('open-security', $codes);
    }

    public function testGroupsWithNoViewableCommandsAreOmitted()
    {
        $this->logInWithPermission('CMS_ACCESS_CMSMain');

        $response = $this->getAjax('admin/cmdk/commands');
        $data = json_decode($response->getBody() ?? '', true);

        $groupCodes = array_column($data['groups'] ?? [], 'code');

        $this->assertContains('content', $groupCodes);
        $this->assertNotContains('admin', $groupCodes);
    }

    public function testSearchRequiresLogin()
    {
        $this->logOut();

        $response = $this->getAjax('admin/cmdk/search?q=test');

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testSearchWithoutQueryReturnsEmptyResults()
    {
        $this->logInWithPermission('CMS_ACCESS_CMSMain');

        $response = $this->getAjax('admin/cmdk/search');
        $data = json_decode($response->getBody() ?? '', true);

        $this->assertSame([], $data['results']);
    }

    private function getAjax(string $url): HTTPResponse
    {
        return $this->get($url, null, ['X-Requested-With' => 'XMLHttpRequest']);
    }

    private function getCommandCodes(HTTPResponse $response): array
    {
        $data = json_decode($response->getBody() ?? '', true);
        $codes = [];

        foreach ($data['groups'] ?? [] as $group) {
            foreach ($group['commands'] as $command) {
                $codes[] = $command['code'];
            }
        }

        return $codes;
    }
}
