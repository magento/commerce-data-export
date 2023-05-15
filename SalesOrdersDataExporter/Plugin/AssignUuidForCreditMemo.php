<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);
namespace Magento\SalesOrdersDataExporter\Plugin;

use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface;
use Magento\DataExporter\Uuid\UuidSaveException;
use Magento\Framework\Model\AbstractModel as CreditMemo;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo as OrderResource;
use Magento\DataExporter\Uuid\UuidManager;

/**
 * Assign UUID to entity if it was not assigned before. Return assigned UUID or thrown Exception on failure
 */
class AssignUuidForCreditMemo
{
    private const TYPE = 'credit_memo';

    private UuidManager $uidManager;
    private CommerceDataExportLoggerInterface $logger;

    /**
     * @param UuidManager $uidManager
     * @param CommerceDataExportLoggerInterface $logger
     */
    public function __construct(
        UuidManager $uidManager,
        CommerceDataExportLoggerInterface $logger
    ) {
        $this->uidManager = $uidManager;
        $this->logger = $logger;
    }

    /**
     * @param OrderResource $subject
     * @param OrderResource $result
     * @param Creditmemo $entity
     * @return OrderResource
     * @throws \Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(OrderResource $subject, OrderResource $result, Creditmemo $entity): OrderResource
    {
        try {
            $entityId = (int) $entity->getId();
            $this->uidManager->assign($entityId, self::TYPE);
        } catch (UuidSaveException) {
            // do nothing, error logged in assign method
        } catch (\Throwable $e) {
            $this->logger->error('Cannot assign UUID to ' . self::TYPE . ' ' . $entity->getId(), ['exception' => $e]);
        }

        return $result;
    }
}
