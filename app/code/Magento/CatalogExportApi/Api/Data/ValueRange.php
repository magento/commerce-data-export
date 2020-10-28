<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 *
 * Generated from et_schema.xml. DO NOT EDIT!
 */

declare(strict_types=1);

namespace Magento\CatalogExportApi\Api\Data;

/**
 * ValueRange entity
 *
 * phpcs:disable Magento2.PHP.FinalImplementation
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ValueRange
{
    /** @var float */
    private $from;

    /** @var float */
    private $to;

    /**
     * Get from
     *
     * @return float
     */
    public function getFrom(): ?float
    {
        return $this->from;
    }

    /**
     * Set from
     *
     * @param float $from
     * @return void
     */
    public function setFrom(?float $from): void
    {
        $this->from = $from;
    }

    /**
     * Get to
     *
     * @return float
     */
    public function getTo(): ?float
    {
        return $this->to;
    }

    /**
     * Set to
     *
     * @param float $to
     * @return void
     */
    public function setTo(?float $to): void
    {
        $this->to = $to;
    }
}
