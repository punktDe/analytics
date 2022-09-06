<?php
declare(strict_types=1);

namespace PunktDe\Analytics\Importer;

/*
 *  (c) 2021 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use League\Flysystem\FileNotFoundException;
use Neos\Flow\Annotations as Flow;
use League\Flysystem\Filesystem;
use League\Flysystem\Plugin\ListFiles;
use League\Flysystem\WebDAV\WebDAVAdapter;
use Neos\Flow\Configuration\Exception as ConfigurationException;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Utility\Environment;
use Neos\Utility\Exception\FilesException;
use Neos\Utility\Files;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Client;

class NextCloudClient
{
    /**
     * @var Filesystem
     */
    protected $fileSystem;

    /**
     * @Flow\InjectConfiguration(path="nextcloud")
     * @var string[][]
     */
    protected $nextCloudConfig;

    /**
     * @Flow\Inject
     * @var Environment
     */
    protected $environment;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $temporaryImportDirectory;

    /**
     * @var string
     */
    protected $configurationNamespace;

    public function __construct(string $configurationNamespace)
    {
        $this->configurationNamespace = $configurationNamespace;
    }

    /**
     * @throws FilesException
     * @throws \Neos\Flow\Utility\Exception
     * @throws ConfigurationException
     */
    public function initializeObject(): void
    {
        $this->validateConfiguration();
        $nextCloudConfig = $this->nextCloudConfig[$this->configurationNamespace];

        $client = new Client([
            'baseUri' => $nextCloudConfig['baseUri'],
            'userName' => $nextCloudConfig['userName'],
            'password' => $nextCloudConfig['password'],
            'authType' => $nextCloudConfig['authType'],
        ]);

        $adapter = new WebDAVAdapter($client, $nextCloudConfig['pathPrefix']);

        $this->fileSystem = new Filesystem($adapter);
        $this->fileSystem->addPlugin(new ListFiles());

        $this->temporaryImportDirectory = Files::concatenatePaths([$this->environment->getPathToTemporaryDirectory(), $nextCloudConfig['temporaryImportDirectoryName']]);
        Files::createDirectoryRecursively($this->temporaryImportDirectory);
    }

    /**
     * @param string $filePattern
     * @return array
     * @throws FileNotFoundException
     */
    public function downloadFiles(string $filePattern = ''): array
    {
        $this->logger->info(sprintf('Starting download for files with pattern %s', $filePattern), LogEnvironment::fromMethodName(__METHOD__));

        $downloadedFiles = [];

        $this->logger->debug(sprintf('Connecting to Nextcloud instance %s with username %s', $this->nextCloudConfig[$this->configurationNamespace]['baseUri'], $this->nextCloudConfig[$this->configurationNamespace]['userName']), LogEnvironment::fromMethodName(__METHOD__));

        foreach ($this->fileSystem->listContents($this->nextCloudConfig[$this->configurationNamespace]['sourcePath']) as $file) {

            if ($filePattern !== '' && !fnmatch($filePattern, $file['basename'])) {
                $this->logger->info('Skipping file ' . $file['basename'], LogEnvironment::fromMethodName(__METHOD__));
                continue;
            }

            $temporaryTargetPathAndFilename = Files::concatenatePaths([$this->temporaryImportDirectory, $file['basename']]);
            $target = fopen($temporaryTargetPathAndFilename, 'wb');
            $sourceStream = $this->fileSystem->readStream($file['path']);

            if ($sourceStream === false) {
                $this->logger->warning(sprintf('Unable to download "%s" from nextcloud.', $file['path']), LogEnvironment::fromMethodName(__METHOD__));
                continue;
            }

            stream_copy_to_stream($sourceStream, $target);
            fclose($target);

            $downloadedFiles[] = $file['basename'];

            $this->logger->info(sprintf('Downloaded file %s to %s', $file['basename'], $temporaryTargetPathAndFilename), LogEnvironment::fromMethodName(__METHOD__));
        }

        return $downloadedFiles;
    }

    /**
     * @return string[]
     */
    public function getDownloadedFilePaths(): array
    {
        return Files::readDirectoryRecursively($this->temporaryImportDirectory);
    }

    /**
     * @throws ConfigurationException
     */
    protected function validateConfiguration(): void
    {
        if (!array_key_exists($this->configurationNamespace, $this->nextCloudConfig)) {
            $logMessage = sprintf('Could not parse nextcloud configuration. Namespace %s is missing', $this->configurationNamespace);
            $this->logger->error($logMessage, LogEnvironment::fromMethodName(__METHOD__));
            throw new ConfigurationException($logMessage, 1623833180);
        }

        $nextCloudConfig = $this->nextCloudConfig[$this->configurationNamespace];
        $necessaryConfigKeys = ['baseUri', 'pathPrefix', 'userName', 'password', 'authType', 'sourcePath', 'temporaryImportDirectoryName'];
        $missingConfigKeys = [];
        foreach ($necessaryConfigKeys as $key) {
            if (!array_key_exists($key, $nextCloudConfig) || empty($nextCloudConfig[$key])) {
                $missingConfigKeys[] = $key;
            }
        }
        if (!empty($missingConfigKeys)) {
            if (count($missingConfigKeys) === 1) {
                $logMessage = sprintf('Could not parse nextcloud configuration. Key %s is missing or empty in namespace %s', implode(', ', $missingConfigKeys), $this->configurationNamespace);
            } else {
                $logMessage = sprintf('Could not parse nextcloud configuration. Keys %s are missing or empty in namespace %s', implode(', ', $missingConfigKeys), $this->configurationNamespace);
            }
            $this->logger->error($logMessage, LogEnvironment::fromMethodName(__METHOD__));
            throw new ConfigurationException($logMessage, 1623833587);
        }
    }
}
