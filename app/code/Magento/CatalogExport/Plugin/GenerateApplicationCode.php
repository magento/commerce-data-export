<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogExport\Plugin;

use Magento\CatalogExport\Model\GenerateDTOs;
use Magento\Setup\Module\Di\App\Task\Operation\ApplicationCodeGenerator;

/**
 * Generate Export API data transfer objects
 */
class GenerateApplicationCode
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
     * Generate export DTOs
     *
     * @param ApplicationCodeGenerator $subject
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeDoOperation(
        ApplicationCodeGenerator $subject
    ): void {
        $this->generateDTOs->execute();
    }
}
