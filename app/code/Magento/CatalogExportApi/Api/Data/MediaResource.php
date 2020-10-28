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
 * MediaResource entity
 *
 * phpcs:disable Magento2.PHP.FinalImplementation
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MediaResource
{
    /** @var string */
    private $url;

    /** @var string */
    private $label;

    /** @var array */
    private $roles;

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
     * Get label
     *
     * @return string
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * Set label
     *
     * @param string $label
     * @return void
     */
    public function setLabel(?string $label): void
    {
        $this->label = $label;
    }

    /**
     * Get roles
     *
     * @return string[]
     */
    public function getRoles(): ?array
    {
        return $this->roles;
    }

    /**
     * Set roles
     *
     * @param string[] $roles
     * @return void
     */
    public function setRoles(?array $roles = null): void
    {
        $this->roles = $roles;
    }
}
