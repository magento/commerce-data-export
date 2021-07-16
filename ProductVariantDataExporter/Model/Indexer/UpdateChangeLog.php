<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductVariantDataExporter\Model\Indexer;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * Update indexer changelog
 */
class UpdateChangeLog
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Update indexer change log
     *
     * @param string $viewId
     * @param array $ids
     */
    public function execute(string $viewId, array $ids): void
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();
        try {
            foreach ($ids as $id) {
                $connection->insert($this->resourceConnection->getTableName($viewId) . '_cl', ['entity_id' => $id]);
            }
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            $this->logger->error(
                sprintf(
                    'Failed to update change log of indexer %s. %s',
                    $viewId,
                    $e->getMessage()
                )
            );
        }
    }
}
