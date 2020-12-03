<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogExport\Setup;

use Magento\CatalogExport\Model\GenerateDTOs;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class for generating ExportApi DTOs
 */
class Recurring implements InstallSchemaInterface
{
    /**
     * @var GenerateDTOs
     */
    private $generateDTOs;

    /**
     * @param GenerateDTOs $generateDTOs
     */
    public function __construct(
        GenerateDTOs $generateDTOs
    ) {
        $this->generateDTOs = $generateDTOs;
    }

    /**
     * @inheritdoc
     * @throws \RuntimeException
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $this->generateDTOs->execute();
    }
}
