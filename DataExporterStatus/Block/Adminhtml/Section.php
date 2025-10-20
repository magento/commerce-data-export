<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DataExporterStatus\Block\Adminhtml;

use Magento\Backend\Block\Template;

class Section extends Template
{
    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->_getData('title');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->toHtml();
    }

    /**
     * @return string
     */
    public function getTabId(): string
    {
        return $this->getNameInLayout();
    }

    /**
     * @return bool
     */
    public function isOpen(): bool
    {
        return $this->_getData('sectionOpen') ?? true;
    }
}
