<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerGroupDataExporter\Test\Integration;

use Magento\DataExporter\Export\Processor;
use Magento\TestFramework\Helper\Bootstrap;

class ExportCustomerGroupsTest extends \PHPUnit\Framework\TestCase
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

    public function testExportCustomerGroups()
    {
        $customerGroups = $this->processor->process(
            'customerGroups',
            [
                ['customerGroupId' => 0],
                ['customerGroupId' => 1],
                ['customerGroupId' => 2],
                ['customerGroupId' => 3]
            ]
        );
        self::assertEquals(4, count($customerGroups));
        self::assertEquals('NOT LOGGED IN', $customerGroups[0]['name']);
        self::assertEquals('General', $customerGroups[1]['name']);
        self::assertEquals('Wholesale', $customerGroups[2]['name']);
        self::assertEquals('Retailer', $customerGroups[3]['name']);

    }
}
