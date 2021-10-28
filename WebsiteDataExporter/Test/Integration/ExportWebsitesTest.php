<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WebsiteDataExporter\Test\Integration;

use Magento\DataExporter\Export\Processor;
use Magento\TestFramework\Helper\Bootstrap;

class ExportWebsitesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Processor
     */
    private $processor;

    /**
     * Setup tests
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = Bootstrap::getObjectManager()->create(Processor::class);
    }

    public function testExportWebsites()
    {
        $websites = $this->processor->process('websites', [['websiteId' => 0], ['websiteId' => 1]]);
        self::assertEquals(2, count($websites));
        self::assertEquals('admin', $websites[0]['websiteCode']);
        self::assertEquals('base', $websites[1]['websiteCode']);
        self::assertEquals('Admin', $websites[0]['name']);
        self::assertEquals('Main Website', $websites[1]['name']);
        self::assertEquals(1, count($websites[0]['storeGroups']));
        self::assertEquals(1, count($websites[1]['storeGroups']));
        self::assertEquals('default', $websites[0]['storeGroups'][0]['storeGroupCode']);
        self::assertEquals('main_website_store', $websites[1]['storeGroups'][0]['storeGroupCode']);
        self::assertEquals('Default', $websites[0]['storeGroups'][0]['name']);
        self::assertEquals('Main Website Store', $websites[1]['storeGroups'][0]['name']);
        self::assertEquals(1, count($websites[0]['storeGroups'][0]['storeViews']));
        self::assertEquals(1, count($websites[1]['storeGroups'][0]['storeViews']));
        self::assertEquals('admin', $websites[0]['storeGroups'][0]['storeViews'][0]['storeViewCode']);
        self::assertEquals('default', $websites[1]['storeGroups'][0]['storeViews'][0]['storeViewCode']);
        self::assertEquals('Admin', $websites[0]['storeGroups'][0]['storeViews'][0]['name']);
        self::assertEquals('Default Store View', $websites[1]['storeGroups'][0]['storeViews'][0]['name']);
    }
}
