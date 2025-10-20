<?php
/**
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2025 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\DataExporterStatus\Service;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporterStatus\Model\Query\ExportStatusQueryInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Feed indexer metadata provider
 */
class FeedIndexStatus
{
    public const SUCCESS_STATUS_CODE = 200;

    private const CONFIG_PATH_ENTITY_ROUTE = 'entityRoute';
    private const CONFIG_PATH_QUERY_INSTANCE = 'query';
    /**
     * @param ResourceConnection $resourceConnection
     * @param FeedIndexMetadata $feedIndexMetadata
     * @param array $feedStatusConfig
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly FeedIndexMetadata $feedIndexMetadata,
        private readonly array $feedStatusConfig
    ) {
    }

    /**
     * Gets source records quantity
     *
     * @return int|null
     */
    public function getSourceRecordsQty(): ?int
    {
        return $this->getExportStatusQuery()?->getSourceRecordsQty($this->feedIndexMetadata);
    }

    /**
     * Gets successfully sent records quantity
     *
     * @return int|null
     */
    public function getSuccessfullySentRecordsQty(): ?int
    {
        if ($this->feedIndexMetadata->isExportImmediately()) {
            $connection = $this->resourceConnection->getConnection();
            $select = $connection->select()
                ->from(
                    ['feed' => $this->resourceConnection->getTableName($this->feedIndexMetadata->getFeedTableName())],
                    ['qty' => new \Zend_Db_Expr('COUNT(*)')]
                )->where('feed.status = ' . self::SUCCESS_STATUS_CODE)
                ->where('feed.is_deleted = ?', 0);

            return (int)$connection->fetchOne($select);
        }

        return null;
    }

    /**
     * Gets failed records quantity
     *
     * @return int|null
     */
    public function getFailedRecordsQty(): ?int
    {
        if ($this->feedIndexMetadata->isExportImmediately()) {
            $connection = $this->resourceConnection->getConnection();
            $select = $connection->select()
                ->from(
                    ['feed' => $this->resourceConnection->getTableName($this->feedIndexMetadata->getFeedTableName())],
                    ['qty' => new \Zend_Db_Expr('COUNT(*)')]
                )->where('feed.status != ' . self::SUCCESS_STATUS_CODE);

            return (int)$connection->fetchOne($select);
        }

        return null;
    }

    public function isSupported(): bool
    {
        return $this->feedIndexMetadata->isExportImmediately() && isset(
            $this->feedStatusConfig[$this->feedIndexMetadata->getFeedName()][self::CONFIG_PATH_QUERY_INSTANCE],
            $this->feedStatusConfig[$this->feedIndexMetadata->getFeedName()][self::CONFIG_PATH_ENTITY_ROUTE]
        );
    }

    public function getEntityRoute(): ?array
    {
        return $this->feedStatusConfig[$this->feedIndexMetadata->getFeedName()][self::CONFIG_PATH_ENTITY_ROUTE] ?? null;
    }

    private function getExportStatusQuery(): ?ExportStatusQueryInterface
    {
        return $this->feedStatusConfig[$this->feedIndexMetadata->getFeedName()][self::CONFIG_PATH_QUERY_INSTANCE] ?? null;
    }
}
