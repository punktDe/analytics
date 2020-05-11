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
use Psr\Log\LoggerInterface;

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
     * Empty the elasticsearch data for the given index.
     * @param string $indexName
     * @throws \Exception
     */
    public function recreateElasticIndex(string $indexName): void
    {
        $this->validateIndexConfiguration($indexName);
        $templateName = $indexName . '_template';

        $this->getClient()->indices()->putTemplate([
            'name' => $templateName,
            'body' => $this->indexConfigurations[$indexName]
        ]);

        try {
            $this->getClient()->indices()->delete(['index' => $indexName . '*']);
        } catch (Missing404Exception $exception) {
            $this->logger->info(sprintf('Index %s could not be removed as it is not found', $indexName), LogEnvironment::fromMethodName(__METHOD__));
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
     * @throws \Exception
     */
    private function validateIndexConfiguration(string $indexName): void
    {
        if (!is_array($this->indexConfigurations)) {
            throw new \Exception('Index configuration must be an array but is ' . var_export($this->indexConfigurations, 1), 1569760272);
        }

        if (!isset($this->indexConfigurations[$indexName])) {
            throw new \Exception(sprintf('Index configuration %s not found, available are %s', $indexName, implode(', ', array_keys($this->indexConfigurations))), 1569760142);
        }
    }
}
