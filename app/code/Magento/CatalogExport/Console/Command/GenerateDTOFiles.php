<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogExport\Console\Command;

use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Phrase;
use Magento\Framework\Xml\Parser;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\DataExporter\Config\ConfigInterface;

/**
 * Class Generate
 *
 * Nette is required for this module - 'composer require nette/php-generator'
 *
 * A destination folder is required to define where the files will be generated.
 *
 * php bin/magento dto:generate --destination-folder /var/www/saas-export/app/code/Magento/CatalogExportApi/Api/Data
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 *
 */
class GenerateDTOFiles extends Command
{
    /**
     * Command Option
     * @var string
     */
    private const DESTINATION_FOLDER = 'destination-folder';

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var File
     */
    private $fileDriver;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var string[]
     */
    private $baseConfigEntities = [
        'Product',
        'Category',
        'ProductVariant',
    ];

    /**
     * @param Parser $parser
     * @param File $fileDriver
     * @param ConfigInterface $config
     * @param $string|null $name
     */
    public function __construct(
        Parser $parser,
        File $fileDriver,
        ConfigInterface $config,
        $name = null
    ) {
        parent::__construct($name);
        $this->parser = $parser;
        $this->fileDriver = $fileDriver;
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('dto:generate');
        $this->setDescription(
            'This will generate the provider class for a module or file. A output path must be defined.'
        );
        $this->addOption(
            self::DESTINATION_FOLDER,
            null,
            InputOption::VALUE_REQUIRED,
            __('Destination Folder')
        );
        parent::configure();
    }

    /**
     * Generate classes
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws LocalizedException
     * @throws RuntimeException
     * @throws FileSystemException
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        if (!$outPutLocation = $input->getOption(self::DESTINATION_FOLDER)) {
            throw new RuntimeException(new Phrase('Destination not defined'));
        }

        $baseNamespace = $this->resolveNameSpace($outPutLocation);
        $parsedEntities = [];
        foreach ($this->baseConfigEntities as $node) {
            $parsedEntities[] = $this->getConfig($node);
        }
        $parsedArray = \array_merge(...$parsedEntities);

        $classesData = $this->prepareDtoClassData($parsedArray, $baseNamespace);

        $this->createDirectory($outPutLocation);
        try {
            $this->generateFiles($classesData, $baseNamespace, $outPutLocation);
        } catch (\Throwable $e) {
            $output->writeln('Error: ' . $e->getMessage());
            return Cli::RETURN_FAILURE;
        }
        $output->writeln('Files has been generated');

        return Cli::RETURN_SUCCESS;
    }

    /**
     * Return entities config.
     *
     * @param string $entity
     * @param array $parsedArray
     * @return array
     */
    private function getConfig(string $entity, $parsedArray = [])
    {
        $parsedEntity = $this->config->get($entity);
        if ($parsedEntity) {
            $parsedArray[$entity] = $parsedEntity;

            foreach ($parsedEntity['field'] as $field) {
                if (!$this->config->isScalar($field['type'])) {
                    $parsedArray = $this->getConfig($field['type'], $parsedArray);
                }
            }
        }
        return $parsedArray;
    }

    /**
     * Build structure required to build DTO
     *
     * @param array  $parsedArray
     * @param string $baseNamespace
     * @return array
     */
    private function prepareDtoClassData(array $parsedArray, string $baseNamespace)
    {
        $result = [];
        if (empty($parsedArray)) {
            return $result;
        }

        foreach ($parsedArray as $schemaConfig) {
            foreach ($schemaConfig['field'] as &$field) {
                $field['type'] = $this->mapType($field['type'], $baseNamespace);
                $field['name'] = lcfirst(str_replace('_', '', ucwords($field['name'], '_')));
            }

            $result[$schemaConfig['name']] = $schemaConfig['field'];
        }

        return $result;
    }

    /**
     * Resolve namespace
     *
     * @param string $filePath
     * @return string
     */
    private function resolveNameSpace(string $filePath): string
    {
        $filePath =  trim($filePath, DIRECTORY_SEPARATOR);
        return str_replace('/', '\\', strstr($filePath, 'Magento'));
    }

    /**
     * Map type
     *
     * @param string $type
     * @param string $baseNameSpace
     * @return string
     */
    private function mapType(string $type, string $baseNameSpace): string
    {
        switch ($type) {
            case 'Int':
                $type = 'int';
                break;
            case 'ID':
            case 'String':
                $type = 'string';
                break;
            case 'Boolean':
                $type = 'bool';
                break;
            case 'Float':
                $type = 'float';
                break;
            default:
                $type = '\\' . $baseNameSpace . '\\' . $type . '[]|null';
        }

        return $type;
    }

    /**
     * Generate files
     *
     * @param array $generateArray
     * @param string $baseNameSpace
     * @param string $baseFileLocation
     * @throws FileSystemException
     * @return void
     */
    private function generateFiles(array $generateArray, string $baseNameSpace, string $baseFileLocation): void
    {
        $nonRequiredMethods = ['id'];
        foreach ($generateArray as $className => $phpClassFields) {
            // phpstan:ignore "Class Nette\PhpGenerator\PhpFile not found."
            $file = new PhpFile();
            $file->addComment('Copyright © Magento, Inc. All rights reserved.');
            $file->addComment('See COPYING.txt for license details.');
            $file->addComment('');
            $file->addComment('Generated from et_schema.xml. DO NOT EDIT!');
            $file->setStrictTypes();
            $namespace = $file->addNamespace($baseNameSpace);
            $class = $namespace->addClass($className);
            $class->addComment($className . ' entity');
            $class->addComment('');
            $class->addComment('phpcs:disable Magento2.PHP.FinalImplementation');
            $class->addComment('@SuppressWarnings(PHPMD.BooleanGetMethodName)');
            $class->addComment('@SuppressWarnings(PHPMD.TooManyFields)');
            $class->addComment('@SuppressWarnings(PHPMD.ExcessivePublicCount)');
            $class->addComment('@SuppressWarnings(PHPMD.ExcessiveClassComplexity)');
            $class->addComment('@SuppressWarnings(PHPMD.CouplingBetweenObjects)');
            foreach ($phpClassFields as $field) {
                $repeated = $field['repeated'];
                $name = $field['name'];
                $type = $field['type'];

                $commentName = preg_replace('/(?<!\ )[A-Z]/', ' $0', $field['name']);
                $property = $class->addProperty($field['name'])->setPrivate();

                if (true === $repeated) {
                    if (substr($type, -4) !== 'null') {
                        $property->addComment('@var ' . 'array');
                    } else {
                        $property->addComment('@var ' . $type);
                    }
                } else {
                    $property->addComment('@var ' . str_replace('[]|null', '', $type));
                }
                $method = $class->addMethod('get' . ucfirst($name));
                $method->addComment('Get ' . strtolower($commentName));
                $method->addComment('');
                if (true === $repeated) {
                    if (substr($type, -4) !== 'null') {
                        $method->addComment('@return ' . $type . '[]');
                    } else {
                        $method->addComment('@return ' . $type);
                    }
                } else {
                    $method->addComment('@return ' . str_replace('[]|null', '', $type));
                }
                if (true === $repeated) {
                    $method->setReturnType('array');
                } else {
                    $method->setReturnType(str_replace('[]|null', '', $type));
                }
                if (!in_array($name, $nonRequiredMethods)) {
                    $method->setReturnNullable();
                }
                $method->addBody('return $this->' . $name . ';');
                $method = $class->addMethod('set' . ucfirst($name));
                $method->addComment('Set ' . strtolower($commentName));
                $method->addComment('');
                if (true === $repeated) {
                    $method->addComment(
                        '@param ' . str_replace('[]|null', '', $type) . '[] $' . $name
                    );
                } else {
                    $method->addComment(
                        '@param ' . str_replace('[]|null', '', $type) . ' $' . $name
                    );
                }
                $method->addComment('@return void');
                if (true === $repeated) {
                    if (!in_array($name, $nonRequiredMethods)) {
                        $method->addParameter($name, null)->setType('array')->setNullable();
                    } else {
                        $method->addParameter($name, null)->setType('array');
                    }
                } else {
                    if (!in_array($name, $nonRequiredMethods)) {
                        $method->addParameter($name)
                            ->setType(str_replace('[]|null', '', $type))
                            ->setNullable();
                    } else {
                        $method->addParameter($name)->setType(str_replace('[]|null', '', $type));
                    }
                }
                $method->setReturnType('void');
                $method->addBody('$this->' . $name . ' = $' . $name . ';');
            }
            // phpstan:ignore "Class Nette\PhpGenerator\PsrPrinter not found."
            $print = new PsrPrinter();
            $this->writeToFile($baseFileLocation . '/' . $className . '.php', $print->printFile($file));
        }
    }

    /**
     * Create directory
     *
     * @param string $outPutLocation
     * @throws FileSystemException
     * @return void
     */
    private function createDirectory(string $outPutLocation): void
    {
        if (!$this->fileDriver->isExists($outPutLocation)) {
            $this->fileDriver->createDirectory($outPutLocation, 0755);
        }
    }

    /**
     * Write to file
     *
     * @param string $fileLocation
     * @param string $output
     * @throws FileSystemException
     * @return void
     */
    private function writeToFile(string $fileLocation, string $output): void
    {
        $resource = $this->fileDriver->fileOpen($fileLocation, 'w');
        $this->fileDriver->fileWrite($resource, $output);
        $this->fileDriver->fileClose($resource);
    }
}
