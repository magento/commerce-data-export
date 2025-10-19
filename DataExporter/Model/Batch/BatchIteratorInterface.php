<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Model\Batch;

interface BatchIteratorInterface extends \Iterator, \Countable
{
    /**
     * Mark batch items for retry.
     *
     * @return void
     */
    public function markBatchForRetry(): void;
}
