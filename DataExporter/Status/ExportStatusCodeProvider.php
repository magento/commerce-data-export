<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\DataExporter\Status;

class ExportStatusCodeProvider
{
    /**
     * List of non-retryable HTTP status codes.
     * Until item hash is changed, feed item will not be sent again on sync attempt
     */
    public const NON_RETRYABLE_HTTP_STATUS_CODE = [200, 400];

    /**
     * Custom code to identify application error
     */
    public const APPLICATION_ERROR = 0;

    /**
     * Custom code to identify failed item error
     * in case if SUCCESS response was returned but it contains failed items which have to be marked as NON-SUCCESS
     * for retry sending feed operation
     */
    public const FAILED_ITEM_ERROR = 1;

    /**
     * Custom code to identify skipped submit process for feed item
     * in case if FEED_SUBMIT_SKIPPED response status was returned - the DB feed saving process will be skipped
     */
    public const FEED_SUBMIT_SKIPPED = -1;

    /**
     * Custom code to identify retryable operation (for entire request or for specific item)
     */
    public const RETRYABLE = 2;
}
