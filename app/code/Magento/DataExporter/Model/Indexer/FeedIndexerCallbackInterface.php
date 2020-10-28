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
interface FeedIndexerCallbackInterface
{
    /**
     * Execute callback
     *
     * @param array $entityData
     * @param int[] $deleteIds
     *
     * @return void
     */
    public function execute(array $entityData, array $deleteIds) : void;
}
