<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogDataExporter\Model\Provider\Category;

use Magento\Catalog\Model\Category\FileInfo;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\FileSystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Category image data provider
 */
class Image
{
    /**
     * Path in /pub/media directory
     */
    const ENTITY_MEDIA_PATH = '/catalog/category';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var FileInfo
     */
    private $fileInfo;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var WriteInterface
     */
    private $mediaDirectory;

    /**
     * @var ReadInterface
     */
    private $baseDirectory;

    /**
     * @var ReadInterface
     */
    private $pubDirectory;

    /**
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param FileInfo $fileInfo
     * @param FileSystem $filesystem
     */
    public function __construct(
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        FileInfo $fileInfo,
        FileSystem $filesystem
    ) {
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->fileInfo = $fileInfo;
        $this->filesystem = $filesystem;
    }

    /**
     * Get provider data
     *
     * @param array $values
     *
     * @return array
     *
     * @throws UnableRetrieveData
     * @throws NoSuchEntityException
     */
    public function get(array $values) : array
    {
        // phpcs:disable
        $output = [];
        $actualStoreCode = $this->storeManager->getStore()->getCode();

        try {
            foreach ($values as $value) {
                $imageUrl = $value['image'];

                if (null === $imageUrl) {
                    continue;
                }

                $this->storeManager->setCurrentStore($value['storeViewCode']);

                $mediaBaseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

                if ($this->fileInfo->isBeginsWithMediaDirectoryPath($imageUrl)) {
                    $imageUrl = \sprintf(
                        '%s/%s',
                        \rtrim($mediaBaseUrl, '/'),
                        \ltrim($this->getRelativePathToMediaDirectory($imageUrl), '/')
                    );
                } elseif (\substr($imageUrl, 0, 1) !== '/') {
                    $imageUrl = \sprintf(
                        '%s/%s/%s',
                        \rtrim($mediaBaseUrl, '/'),
                        \ltrim(
                            FileInfo::ENTITY_MEDIA_PATH,
                            '/'
                        ),
                        \basename($imageUrl)
                    );
                }

                $output[] = [
                    'categoryId' => $value['categoryId'],
                    'storeViewCode' => $value['storeViewCode'],
                    'image' => $imageUrl,
                ];
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            throw new UnableRetrieveData('Unable to retrieve category image');
        } finally {
            $this->storeManager->setCurrentStore($actualStoreCode);
        }

        // phpcs:enable
        return $output;
    }

    /**
     * Get file relative path to media directory
     *
     * Note: This function and the other image path related private functions below have been
     * taken from Magento\Catalog\Model\Category\FileInfo.php to preserve backward compatibility
     * for Magento versions < 2.4. A public function getRelativePathToMediaDirectory() has been
     * added to this class in version 2.4 to provide the same functionality.
     * If we move to an approach where we use different branches for different Magento versions, we can remove this in
     * versions >= 2.4 versions and use Magento\Catalog\Model\Category\FileInfo::getRelativePathToMediaDirectory()
     *
     * @param string $filename
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function getRelativePathToMediaDirectory(string $filename): string
    {
        return $this->getFilePath($filename);
    }

    /**
     * Construct and return file subpath based on filename relative to media directory
     *
     * @param string $fileName
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function getFilePath($fileName)
    {
        $filePath = $this->removeStorePath($fileName);
        $filePath = ltrim($filePath, '/');

        $mediaDirectoryRelativeSubpath = $this->getMediaDirectoryPathRelativeToBaseDirectoryPath($filePath);
        $isFileNameBeginsWithMediaDirectoryPath = $this->fileInfo->isBeginsWithMediaDirectoryPath($fileName);

        // if the file is not using a relative path, it resides in the catalog/category media directory
        $fileIsInCategoryMediaDir = !$isFileNameBeginsWithMediaDirectoryPath;

        if ($fileIsInCategoryMediaDir) {
            $filePath = self::ENTITY_MEDIA_PATH . '/' . $filePath;
        } else {
            $filePath = substr($filePath, strlen($mediaDirectoryRelativeSubpath));
        }

        return $filePath;
    }

    /**
     * Clean store path in case if it's exists
     *
     * @param string $path
     * @return string
     */
    private function removeStorePath(string $path): string
    {
        $result = $path;
        try {
            $storeUrl = $this->storeManager->getStore()->getBaseUrl();
        } catch (NoSuchEntityException $e) {
            return $result;
        }
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $path = parse_url($path, PHP_URL_PATH);
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $storePath = parse_url($storeUrl, PHP_URL_PATH);
        $storePath = rtrim($storePath, '/');

        $result = preg_replace('/^' . preg_quote($storePath, '/') . '/', '', $path);
        return $result;
    }

    /**
     * Get media directory subpath relative to base directory path
     *
     * @param string $filePath
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function getMediaDirectoryPathRelativeToBaseDirectoryPath(string $filePath = '')
    {
        $baseDirectory = $this->getBaseDirectory();
        $baseDirectoryPath = $baseDirectory->getAbsolutePath();
        $mediaDirectoryPath = $this->getMediaDirectory()->getAbsolutePath();
        $pubDirectoryPath = $this->getPubDirectory()->getAbsolutePath();

        $mediaDirectoryRelativeSubpath = substr($mediaDirectoryPath, strlen($baseDirectoryPath));
        $pubDirectory = $baseDirectory->getRelativePath($pubDirectoryPath);

        if ($pubDirectory && strpos($mediaDirectoryRelativeSubpath, $pubDirectory) === 0
            && strpos($filePath, $pubDirectory) !== 0) {
            $mediaDirectoryRelativeSubpath = substr($mediaDirectoryRelativeSubpath, strlen($pubDirectory));
        }

        return $mediaDirectoryRelativeSubpath;
    }

    /**
     * @return WriteInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function getMediaDirectory()
    {
        if ($this->mediaDirectory === null) {
            $this->mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        }
        return $this->mediaDirectory;
    }

    /**
     * Get Base Directory read instance
     *
     * @return ReadInterface
     */
    private function getBaseDirectory()
    {
        if (!isset($this->baseDirectory)) {
            $this->baseDirectory = $this->filesystem->getDirectoryRead(DirectoryList::ROOT);
        }

        return $this->baseDirectory;
    }

    /**
     * Get Pub Directory read instance
     *
     * @return ReadInterface
     */
    private function getPubDirectory()
    {
        if (!isset($this->pubDirectory)) {
            $this->pubDirectory = $this->filesystem->getDirectoryRead(DirectoryList::PUB);
        }

        return $this->pubDirectory;
    }
}
