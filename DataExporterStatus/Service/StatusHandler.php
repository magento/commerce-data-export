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

use Magento\DataExporter\Status\ExportStatusCodeProvider;
use Magento\Framework\DB\Select;

/**
 * Feed indexer metadata provider
 */
class StatusHandler
{
    public const STATUS_AWAITING_SUBMISSION = -7;

    private const UI_STATUS_SUCCESS = 'success';
    private const UI_STATUS_RETRYABLE = 'retry';
    private const UI_STATUS_USER_ERROR = 'u_error';

    private const FEED_STATUS_USER_ERROR = [
        400 => 400,
        401 => 401,
        403 => 403,
        ExportStatusCodeProvider::FAILED_ITEM_ERROR => ExportStatusCodeProvider::FAILED_ITEM_ERROR];
    private const FEED_STATUS_RETRYABLE_REVERSED = self::FEED_STATUS_USER_ERROR + [200 => 200];

    public static function feedItemStatusToUIStatus(int $status): string
    {
        return match(true) {
            $status == 200 => StatusHandler::UI_STATUS_SUCCESS,
            in_array($status, self::FEED_STATUS_USER_ERROR) => StatusHandler::UI_STATUS_USER_ERROR,
            default => StatusHandler::UI_STATUS_RETRYABLE
        };
    }

    /**
     * @param string $status
     * @param Select $select
     * @return void
     */
    public static function applyFilterFromUiStatus(string $status, \Magento\Framework\DB\Select $select): void
    {
        switch ($status) {
            case StatusHandler::UI_STATUS_SUCCESS:
                $select->where('main_table.status = ?', 200)
                    ->where('main_table.is_deleted = ?', 0);
                break;
            case StatusHandler::UI_STATUS_USER_ERROR:
                $select->where(
                    'main_table.status IN (?)',
                    self::FEED_STATUS_USER_ERROR
                );
                break;
            case StatusHandler::UI_STATUS_RETRYABLE:
                $select->where(
                    'main_table.status NOT IN (?)',
                    self::FEED_STATUS_RETRYABLE_REVERSED
                );
                break;
        }
    }

    public static function getUIStatusLabels(): array
    {
        return [
            StatusHandler::UI_STATUS_SUCCESS => __('Submitted to service'),
            StatusHandler::UI_STATUS_RETRYABLE => __('Failed, will retry'),
            StatusHandler::UI_STATUS_USER_ERROR => __('Failed, require attention'),
            StatusHandler::STATUS_AWAITING_SUBMISSION => __('Awaiting submission'),
        ];
    }

    public static function isFailedStatus(int $status): bool
    {
        return in_array($status, StatusHandler::FEED_STATUS_USER_ERROR, true)
            || !in_array($status, StatusHandler::FEED_STATUS_RETRYABLE_REVERSED, true);
    }

    public static function getOrigStatusLabels(): array
    {
        return [
            StatusHandler::STATUS_AWAITING_SUBMISSION => __('Awaiting submission'),
            ExportStatusCodeProvider::APPLICATION_ERROR => __('Application error'),
            ExportStatusCodeProvider::RETRYABLE => __('Not accepted (to be retried)'),
            ExportStatusCodeProvider::FAILED_ITEM_ERROR => __('Client error (require attention)'),
            200 => __('Submitted to service'),
            400 => __('Data payload error (status code: 400)'),
            401 => __('Unauthorized request (status code: 401)'),
            403 => __('Permission denied (status code: 403)'),
            429 => __('Too many requests (status code: 429)'),
            500 => __('Internal Server Error (status code: 500)'),
            503 => __('Service Unavailable (status code: 503)'),
        ];
    }
}
