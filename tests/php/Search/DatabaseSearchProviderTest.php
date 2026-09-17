<?php

namespace Wilr\AdminCmdk\Tests\Search;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Group;
use SilverStripe\Security\InheritedPermissions;
use Wilr\AdminCmdk\Search\DatabaseSearchProvider;

/**
 * Proves the default search provider filters out records the searching member cannot view -
 * matching keywords alone is not enough for a record to appear in results.
 */
class DatabaseSearchProviderTest extends SapphireTest
{
    protected $usesDatabase = true;

    private SiteTree $publicPage;

    private SiteTree $restrictedPage;

    private Group $viewerGroup;

    protected function setUp(): void
    {
        parent::setUp();

        Config::modify()->set(DatabaseSearchProvider::class, 'searchable_classes', [
            SiteTree::class => [
                'fields' => ['Title'],
                'icon' => 'font-icon-p-file',
                'group' => 'Pages',
            ],
        ]);

        $this->viewerGroup = Group::create(['Title' => 'Widget viewers']);
        $this->viewerGroup->write();

        $this->publicPage = SiteTree::create([
            'Title' => 'Widget Landing Page',
            'CanViewType' => InheritedPermissions::ANYONE,
        ]);
        $this->publicPage->write();
        $this->publicPage->publishSingle();

        $this->restrictedPage = SiteTree::create([
            'Title' => 'Widget Internal Roadmap',
            'CanViewType' => InheritedPermissions::ONLY_THESE_USERS,
        ]);
        $this->restrictedPage->write();
        $this->restrictedPage->ViewerGroups()->add($this->viewerGroup);
        $this->restrictedPage->publishSingle();
    }

    public function testOutsiderOnlySeesPublicPage()
    {
        $outsider = $this->createMemberWithPermission('CMS_ACCESS_CMSMain');

        $results = DatabaseSearchProvider::create()->search('Widget', $outsider);
        $titles = array_map(fn ($result) => $result->getTitle(), $results);

        $this->assertContains('Widget Landing Page', $titles);
        $this->assertNotContains('Widget Internal Roadmap', $titles);
    }

    public function testMemberInViewerGroupSeesBothPages()
    {
        $member = $this->createMemberWithPermission('CMS_ACCESS_CMSMain');
        $member->Groups()->add($this->viewerGroup);

        $results = DatabaseSearchProvider::create()->search('Widget', $member);
        $titles = array_map(fn ($result) => $result->getTitle(), $results);

        $this->assertContains('Widget Landing Page', $titles);
        $this->assertContains('Widget Internal Roadmap', $titles);
    }

    public function testAdminSeesBothPages()
    {
        $admin = $this->createMemberWithPermission('ADMIN');

        $results = DatabaseSearchProvider::create()->search('Widget', $admin);
        $titles = array_map(fn ($result) => $result->getTitle(), $results);

        $this->assertContains('Widget Landing Page', $titles);
        $this->assertContains('Widget Internal Roadmap', $titles);
    }

    public function testNonMatchingQueryReturnsNoResults()
    {
        $admin = $this->createMemberWithPermission('ADMIN');

        $results = DatabaseSearchProvider::create()->search('NoSuchPageExists', $admin);

        $this->assertSame([], $results);
    }

    public function testQueryShorterThanMinimumLengthReturnsNoResults()
    {
        $admin = $this->createMemberWithPermission('ADMIN');

        $results = DatabaseSearchProvider::create()->search('W', $admin);

        $this->assertSame([], $results);
    }

    public function testResultsAreLinkedToCmsEditView()
    {
        $admin = $this->createMemberWithPermission('ADMIN');

        $results = DatabaseSearchProvider::create()->search('Widget Landing', $admin);

        $this->assertNotEmpty($results);
        $this->assertStringContainsString((string) $this->publicPage->ID, $results[0]->getLink());
    }
}
