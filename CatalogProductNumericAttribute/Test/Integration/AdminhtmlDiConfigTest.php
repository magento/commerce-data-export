<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace AdobeCommerce\CatalogProductNumericAttribute\Test\Integration;

use Magento\Eav\Model\Adminhtml\System\Config\Source\Inputtype;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Ui\DataProvider\Mapper\FormElement;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the "numeric" admin DI arguments survive the adminhtml area config merge.
 *
 * Core declares Inputtype "optionsArray" and FormElement "mappings" in etc/adminhtml/di.xml. Area config replaces
 * a global argument as a whole, so these entries only take effect from the module's etc/adminhtml/di.xml.
 *
 * @magentoAppArea adminhtml
 */
class AdminhtmlDiConfigTest extends TestCase
{
    public function testInputTypeOptionsContainNumber(): void
    {
        $options = Bootstrap::getObjectManager()->create(Inputtype::class)->toOptionArray();
        $labels = array_column($options, 'label', 'value');

        // Core types only exist in the adminhtml area, so this guards against evaluating global config.
        $this->assertArrayHasKey('text', $labels, 'Core admin input types are missing');
        $this->assertArrayHasKey(
            'numeric',
            $labels,
            '"Number" must be available in the admin "Catalog Input Type for Store Owner" dropdown'
        );
        $this->assertSame('Number', (string)$labels['numeric']);
    }

    public function testFormElementMapsNumericToInput(): void
    {
        $mappings = Bootstrap::getObjectManager()->create(FormElement::class)->getMappings();

        $this->assertSame('input', $mappings['text'] ?? null, 'Core admin form element mappings are missing');
        $this->assertSame(
            'input',
            $mappings['numeric'] ?? null,
            'frontend_input "numeric" must map to the "input" form element in admin'
        );
    }
}
