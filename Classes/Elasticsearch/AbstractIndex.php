<?php
declare(strict_types=1);

namespace PunktDe\Analytics\Elasticsearch;

/*
 *  (c) 2019 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use Elasticsearch\Client;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;
use Psr\Log\LoggerInterface;

abstract class AbstractIndex implements IndexInterface
{
    /**
     * @Flow\Inject
     * @var ElasticsearchService
     */
    protected $elasticsearchService;

    protected $indexName = '';

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    public function __call($name, $arguments)
    {
        return $this->elasticsearchService->getClient()->$name(...$arguments);
    }

    /**
     * @param string $documentID
     * @return array|callable
     */
    public function deleteById(string $documentID)
    {
        return $this->elasticsearchService->getClient()->deleteByQuery([
            'index' => $this->indexName . '*',
            'body' => [
                'query' => [
                    'match' => [
                        '_id' => $documentID
                    ]
                ]
            ]
        ]);
    }

    /**
     * @param \DateTime $dateStart
     * @param \DateTime $dateEnd
     * @param string $indexSuffix
     * @return array|callable
     */
    public function deleteForTimeRange(\DateTime $dateStart, \DateTime $dateEnd, string $indexSuffix = '*')
    {
        $result = [];
        $body = [
            'query' => [
                'range' => [
                    'date' => [
                        'gte' => $dateStart->format('c'),
                        'lte' => $dateEnd->format('c'),
                    ]
                ]
            ]
        ];

        try {
            $result = $this->elasticsearchService->getClient()->deleteByQuery([
                'index' => $this->indexName . $indexSuffix,
                'body' => $body,
            ]);
        } catch (\Exception $exception) {
            $this->logger->error('Error while deleting data from index ' . $this->indexName, LogEnvironment::fromMethodName(__METHOD__));
        }

        return $result;
    }

    /**
     * @return \DateTime|null
     */
    public function getOldestDocumentDate(): ?\DateTime
    {
        $result = $this->elasticsearchService->getClient()->search([
            'index' => $this->indexName . '*',
            'body' => [
                'size' => 0,
                'aggs' => [
                    'oldest_document' => [
                        'min' => [
                            'field' => 'date',
                            'format' => 'date_hour_minute_second'
                        ]
                    ]
                ]
            ]
        ]);

        $dateString = $result['aggregations']['oldest_document']['value_as_string'] ?? null;

        if ($dateString === null) {
            return null;
        }

        return \DateTime::createFromFormat('Y-m-d\TH:i:s', $dateString);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->indexName;
    }
}
