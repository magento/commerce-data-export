<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TestFramework\Listener;

use Magento\TestFramework\Helper;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;

/**
 * Run Magento\CatalogExport\Model\GenerateDTOs::execute before testsuite to generate DTO classes required REST endpoint
 */
class DiCompile implements \PHPUnit\Framework\TestListener
{
    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function addError(Test $test, \Throwable $t, float $time): void
    {
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function addWarning(Test $test, Warning $e, float $time): void
    {
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function addFailure(Test $test, AssertionFailedError $e, float $time): void
    {
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function addIncompleteTest(Test $test, \Throwable $t, float $time): void
    {
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function addRiskyTest(Test $test, \Throwable $t, float $time): void
    {
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function addSkippedTest(Test $test, \Throwable $t, float $time): void
    {
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function startTestSuite(TestSuite $suite): void
    {
        $generate = Helper\Bootstrap::getObjectManager()->get(\Magento\CatalogExport\Model\GenerateDTOs::class);
        $generate->execute();
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function endTestSuite(TestSuite $suite): void
    {
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function startTest(Test $test): void
    {
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function endTest(Test $test, float $time): void
    {
    }
}
