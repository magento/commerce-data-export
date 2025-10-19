<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

use Magento\Framework\App\Filesystem\DirectoryList;

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var $mediaDirectory \Magento\Framework\Filesystem\Directory\WriteInterface */
$mediaDirectory = $objectManager->get(\Magento\Framework\Filesystem::class)
    ->getDirectoryWrite(DirectoryList::MEDIA);
$fileName = 'category_test_image.jpg';
$filePath = 'catalog/category/' . $fileName;
$mediaDirectory->create('catalog/category');

copy(__DIR__ . DIRECTORY_SEPARATOR . $fileName, $mediaDirectory->getAbsolutePath($filePath));
