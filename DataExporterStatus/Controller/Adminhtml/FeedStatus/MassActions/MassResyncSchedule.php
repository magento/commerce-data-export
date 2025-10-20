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

namespace Magento\DataExporterStatus\Controller\Adminhtml\FeedStatus\MassActions;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\DataExporter\Model\FeedPool;
use Magento\DataExporter\Service\FeedItemsResyncScheduler;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;

class MassResyncSchedule extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Magento_DataExporterStatus::manage';

    public function __construct(
        Context $context,
        private readonly FeedPool $feedPool,
        private readonly  FeedItemsResyncScheduler $feedItemsResyncScheduler,
    ) {
        parent::__construct($context);
    }

    /**
     * Execute mass resync schedule action
     *
     * @return Redirect
     */
    public function execute()
    {
        $selected = $this->getRequest()->getParam('selected');
        $excluded = $this->getRequest()->getParam('excluded', 'false');
        $feedName = $this->getFeedName();

        try {
            if (empty($selected) && $excluded !== 'false') {
                $this->messageManager->addErrorMessage(__('Please select items to schedule resync.'));
                return $this->createRedirectResult($feedName);
            }

            if (!$feedName || !($feed = $this->feedPool->getFeed($feedName))) {
                $this->messageManager->addErrorMessage(__('Could not determine feed for resync operation.'
                    . ' Please ensure you are on a specific feed page.'));
                return $this->createRedirectResult();
            }

            $feedMetadata = $feed->getFeedMetadata();

            $submittedCount = $this->feedItemsResyncScheduler->execute($feedMetadata, $selected);
            if ($submittedCount == -1) {
                $this->messageManager->addSuccessMessage(
                    __(
                        'Full resync was scheduled for "%1" feed.',
                        $feedName
                    )
                );
            } elseif ($submittedCount > 0) {
                $this->messageManager->addWarningMessage(
                    __(
                        'A total of %1 record(s) have been scheduled for resync in "%2" feed.',
                        $submittedCount,
                        $feedName
                    )
                );
            } else {
                $this->messageManager->addWarningMessage(
                    __(
                        'No feed records have been marked for schedule resync in feed "%1".',
                        $feedName
                    )
                );
            }
        } catch (\InvalidArgumentException $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred while processing the resync: %1', $e->getMessage())
            );
        }

        return $this->createRedirectResult($feedName);
    }

    /**
     * Get feed name from params
     *
     * @return string|null
     */
    private function getFeedName(): ?string
    {
        $feedName = $this->getRequest()->getParam('feed');
        return $feedName ?? null;
    }

    /**
     * Create redirect result
     *
     * @param ?string $feedName
     * @return Redirect
     */
    private function createRedirectResult(?string $feedName = null): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if ($feedName) {
            $resultRedirect->setPath('*/*/', ['feed' => $feedName]);
        } else {
            $resultRedirect->setPath('*/*/');
        }
        
        return $resultRedirect;
    }
}