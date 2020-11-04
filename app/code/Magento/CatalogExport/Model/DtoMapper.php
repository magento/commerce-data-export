<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogExport\Model;

use Magento\Framework\Reflection\MethodsMap;
use Magento\Framework\Api\ObjectFactory;
use Magento\Framework\Reflection\TypeProcessor;

/**
 * Data object mapper.
 */
class DtoMapper
{
    /**
     * @var ObjectFactory
     */
    private $objectFactory;

    /**
     * @var TypeProcessor
     */
    private $typeProcessor;

    /**
     * @var MethodsMap
     */
    private $methodsMapProcessor;

    /**
     * @param ObjectFactory $objectFactory
     * @param TypeProcessor $typeProcessor
     * @param MethodsMap    $methodsMapProcessor
     */
    public function __construct(
        ObjectFactory $objectFactory,
        TypeProcessor $typeProcessor,
        MethodsMap $methodsMapProcessor
    ) {
        $this->objectFactory = $objectFactory;
        $this->typeProcessor = $typeProcessor;
        $this->methodsMapProcessor = $methodsMapProcessor;
    }

    /**
     * Populate data object using data in array format.
     *
     * @param mixed $dataObject
     * @param array $data
     * @param string $interfaceName
     * @return void
     */
    public function populateWithArray($dataObject, array $data, string $interfaceName): void
    {
        $this->setDataValues($dataObject, $data, $interfaceName);
    }

    /**
     * Update Data Object with the data from array
     *
     * @param mixed $dataObject
     * @param array $data
     * @param string $interfaceName
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function setDataValues($dataObject, array $data, $interfaceName)
    {
        $dataObjectMethods = get_class_methods(\get_class($dataObject));
        foreach ($data as $key => $value) {
            /* First, verify is there any setter for the key on the Service Data Object */
            $camelCaseKey = \Magento\Framework\Api\SimpleDataObjectConverter::snakeCaseToUpperCamelCase($key);
            $methodName = 'set' . $camelCaseKey;
            if (\in_array($methodName, $dataObjectMethods, true)) {
                if (!is_array($value)) {
                    $dataObject->$methodName($value);
                } else {
                    $getterMethodName = 'get' . $camelCaseKey;
                    $this->setComplexValue($dataObject, $getterMethodName, $methodName, $value, $interfaceName);
                }
            }
        }

        return $this;
    }

    /**
     * Set complex value for dataObject.
     *
     * @param mixed $dataObject
     * @param string $getterMethodName
     * @param string $methodName
     * @param array $value
     * @param string $interfaceName
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function setComplexValue(
        $dataObject,
        $getterMethodName,
        $methodName,
        array $value,
        $interfaceName
    ) {
        if ($interfaceName === null) {
            $interfaceName = \get_class($dataObject);
        }
        $returnType = $this->methodsMapProcessor->getMethodReturnType($interfaceName, $getterMethodName);
        if ($this->typeProcessor->isTypeSimple($returnType)) {
            $dataObject->$methodName($value);
            return $this;
        }

        if ($this->typeProcessor->isArrayType($returnType)) {
            $type = $this->typeProcessor->getArrayItemType($returnType);
            $objects = [];
            foreach ($value as $arrayElementData) {
                $object = $this->objectFactory->create($type, $arrayElementData);
                $this->populateWithArray($object, $arrayElementData, $type);
                $objects[] = $object;
            }
            $dataObject->$methodName($objects);
            return $this;
        } else {
            $object = $this->objectFactory->create($returnType, $value);
            $this->populateWithArray($object, $value, $returnType);
        }
        $dataObject->$methodName($object);
        return $this;
    }
}
