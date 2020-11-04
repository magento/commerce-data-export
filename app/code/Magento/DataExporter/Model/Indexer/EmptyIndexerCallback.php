<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\DataExporter\Model\Indexer;

/**
 * Allows to perform an action after index is updated
 */
class EmptyIndexerCallback implements FeedIndexerCallbackInterface
{
    /**
     * @inheritdoc
     * phpcs:disable Magento2.CodeAnalysis.EmptyBlock
     */
    public function execute(array $entityData, array $deleteIds) : void
    {
    }
}
