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
 * UrlRewrite entity
 *
 * phpcs:disable Magento2.PHP.FinalImplementation
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UrlRewrite
{
    /** @var string */
    private $url;

    /** @var \Magento\CatalogExportApi\Api\Data\UrlRewriteParameter[]|null */
    private $parameters;

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return void
     */
    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    /**
     * Get parameters
     *
     * @return \Magento\CatalogExportApi\Api\Data\UrlRewriteParameter[]|null
     */
    public function getParameters(): ?array
    {
        return $this->parameters;
    }

    /**
     * Set parameters
     *
     * @param \Magento\CatalogExportApi\Api\Data\UrlRewriteParameter[] $parameters
     * @return void
     */
    public function setParameters(?array $parameters = null): void
    {
        $this->parameters = $parameters;
    }
}
