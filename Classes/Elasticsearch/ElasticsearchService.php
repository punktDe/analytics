<?php
declare(strict_types=1);

namespace PunktDe\Analytics\Elasticsearch;

/*
 *  (c) 2019 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use Elasticsearch\Common\Exceptions\Missing404Exception;
use Neos\Flow\Annotations as Flow;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Reflection\ReflectionService;
use Psr\Log\LoggerInterface;
use PunktDe\Analytics\Elasticsearch\IndexConfiguration\IndexConfigurationPostProcessorInterface;

/**
 * @Flow\Scope("singleton")
 */
class ElasticsearchService
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var array
     * @Flow\InjectConfiguration(path="elasticsearch.server")
     */
    protected $clientConfiguration;

    /**
     * @var array
     * @Flow\InjectConfiguration(path="elasticsearch.indexConfiguration")
     */
    protected $indexConfigurations;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @Flow\Inject
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * Empty the elasticsearch data for the given index.
     * @param string $indexName
     * @throws \Exception
     */
    public function recreateElasticIndex(string $indexName): void
    {
        $this->logger->info(sprintf('Recreating index %s', $indexName), LogEnvironment::fromMethodName(__METHOD__));

        $templateName = $indexName . '_template';

        $this->getClient()->indices()->putTemplate([
            'name' => $templateName,
            'body' => $this->getIndexConfiguration($indexName)
        ]);

        $this->logger->info(sprintf('Successfully transferred template %s', $templateName), LogEnvironment::fromMethodName(__METHOD__));
        $indexPattern = $indexName . '*';

        try {
            $this->getClient()->indices()->delete(['index' => $indexPattern]);
            $this->logger->info(sprintf('Successfully removed indices with pattern %s', $indexPattern), LogEnvironment::fromMethodName(__METHOD__));
        } catch (Missing404Exception $exception) {
            $this->logger->info(sprintf('Index with pattern %s could not be removed as it is not found', $indexPattern), LogEnvironment::fromMethodName(__METHOD__));
        }
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        if ($this->client === null) {
            $this->client = ClientBuilder::create()
                ->setHosts([$this->clientConfiguration])
                ->build();
        }

        return $this->client;
    }

    /**
     * @param string $indexName
     * @param string $teamName
     * @param string $userName
     * @return string
     */
    public static function buildSplitIndexName(string $indexName, ?string $teamName, ?string $userName): string
    {
        return implode('.', [
            $indexName,
            strtolower((string)$teamName === '' ? 'misc' : $teamName),
            strtolower((string)$userName === '' ? 'misc' : $userName),
        ]);
    }

    /**
     * @param string $indexName
     * @return array
     * @throws \Exception
     */
    private function getIndexConfiguration(string $indexName): array
    {
        if (!is_array($this->indexConfigurations)) {
            throw new \Exception('Index configuration must be an array but is ' . var_export($this->indexConfigurations, 1), 1569760272);
        }

        if (!isset($this->indexConfigurations[$indexName])) {
            throw new \Exception(sprintf('Index configuration %s not found, available are %s', $indexName, implode(', ', array_keys($this->indexConfigurations))), 1569760142);
        }

        $indexConfiguration = $this->indexConfigurations[$indexName];
        $indexConfigurationPostProcessorClasses = $this->reflectionService->getAllImplementationClassNamesForInterface(IndexConfigurationPostProcessorInterface::class);

        foreach ($indexConfigurationPostProcessorClasses as $configurationPostProcessorClass) {
            if ($configurationPostProcessorClass::isSuitableFor($indexName)) {
                /** @var IndexConfigurationPostProcessorInterface $configurationPostProcessor */
                $configurationPostProcessor = new $configurationPostProcessorClass();
                $indexConfiguration = $configurationPostProcessor->process($indexConfiguration);
            }
        }

        return $indexConfiguration;
    }
}
