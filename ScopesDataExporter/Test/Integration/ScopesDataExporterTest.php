<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ScopesDataExporter\Test\Integration;

use Magento\DataExporter\Export\Processor;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

final class ScopesDataExporterTest extends TestCase
{
    private const EXPECTED_WEBSITE_DATA = [
        [
            "websiteId" => "1",
            "websiteCode" => "base",
            "stores" => [
                [
                    "storeId" => "1",
                    "storeCode" => "main_website_store",
                    "storeViews" => [
                        ["storeViewId" => "1", "storeViewCode" => "default"]
                    ]
                ]
            ]
        ]
    ];

    private const EXPECTED_CUSTOMER_GROUP_DATA = [
        [
            "customerGroupId" => "0",
            "customerGroupCode" => "b6589fc6ab0dc82cf12099d1c2d40ab994e8410c",
            "websites" => [
                ["websiteId" => "1", "websiteCode" => "base"]
            ]
        ],
        [
            "customerGroupId" => "1",
            "customerGroupCode" => "356a192b7913b04c54574d18c28d46e6395428ab",
            "websites" => [
                ["websiteId" => 1, "websiteCode" => "base"]
            ]
        ]
    ];

    /**
     * @var Processor
     */
    private $processor;

    /**
     * @inerhitDoc
     */
    protected function setUp(): void
    {
        $this->processor = Bootstrap::getObjectManager()->create(Processor::class);
    }

    /**
     * Checks that the data obtained from the database is structured according to the required scopes for websites.
     *
     * @return void
     */
    public function testShouldExportScopesWebsitesData(): void
    {
        $websites = $this->processor->process('scopesWebsite', [['websiteId' => 0], ['websiteId' => 1]]);
        self::assertIsArray($websites, "Expecting data array");
        self::assertEquals(self::EXPECTED_WEBSITE_DATA, $websites, "Website data mismatch");
    }

    /**
     * Checks that the data obtained from the database is structured according to the required scopes for customer
     * groups and their associated websites.
     *
     * @return void
     */
    public function testShouldExportScopesCustomerGroupData(): void
    {
        $customerGroups = $this->processor->process('scopesCustomerGroup', [['customerGroupId' => 0], ['customerGroupId' => 1]]);
        self::assertIsArray($customerGroups, "Expecting a data array");
        self::assertEquals(self::EXPECTED_CUSTOMER_GROUP_DATA, $customerGroups, "Customer data mismatch");
    }
}
