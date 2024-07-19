<?php
/**
 * Copyright 2021 Adobe
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
namespace Magento\QueryXml\Model\DB;

/**
 * Resolver for source names
 */
class NameResolver
{
    /**
     * Returns element for name
     *
     * @param array $elementConfig
     * @return string
     */
    public function getName($elementConfig)
    {
        return $elementConfig['name'];
    }

    /**
     * Returns alias
     *
     * @param array $elementConfig
     * @return string
     */
    public function getAlias($elementConfig)
    {
        $alias = $this->getName($elementConfig);
        if (isset($elementConfig['alias'])) {
            $alias = $elementConfig['alias'];
        }
        return $alias;
    }
}
