<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductOverrideDataExporter\Test\Integration;

use Magento\TestFramework\Helper\Bootstrap;

class ProductOverrideQueryTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var \Magento\QueryXml\Model\QueryFactory
     */
    private $queryFactory;

    protected function setUp(): void
    {
        $this->queryFactory = Bootstrap::getObjectManager()->create(\Magento\QueryXml\Model\QueryFactory::class);
    }

    public function getProductOverrideQueries(): array
    {
        return [
            [
                'queryName' => 'productPriceOverrides',
                'file' => '/_files/productPriceOverrides.txt'
            ],
            [
                'queryName' => 'configurableProductPriceOverrides',
                'file' => '/_files/configurableProductPriceOverrides.txt'
            ]
        ];
    }

    /**
     * @param $queryName
     * @param $file
     * @dataProvider getProductOverrideQueries
     */
    public function testProductOverrideQueries($queryName, $file)
    {
        $expectedSql = file_get_contents(__DIR__ . $file);
        $actualSql = $this->queryFactory->create($queryName)->getSelect()->assemble();
        self::assertEquals($expectedSql, $actualSql);
    }
}
