<?php
declare(strict_types=1);

namespace PunktDe\Analytics\Transfer;

/*
 *  (c) 2019 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use Doctrine\ORM\Internal\Hydration\IterableResult;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;
use Psr\Log\LoggerInterface;
use PunktDe\Analytics\Elasticsearch\IndexInterface;
use PunktDe\Analytics\Persistence\RepositoryInterface;
use PunktDe\Analytics\Processor\ElasticsearchProcessorInterface;

class AbstractTransferJob
{
    protected const BULK_INDEX_SIZE = 1000;

    /**
     * @var ElasticsearchProcessorInterface
     */
    protected $processor;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var int
     */
    private $counter;

    /**
     * @var int
     */
    private $logEvery = 10000;

    /**
     * @var int
     */
    private $lastStatsDate;

    /**
     * @var string
     */
    protected $jobName;

    /**
     * @var IndexInterface
     */
    protected $index;

    /**
     * @var array
     */
    protected $bulkIndexStorage = [];

    /**
     * @var int
     */
    protected $bulkIndexStorageCounter = 0;

    /**
     * @param string $jobName
     * @throws \Exception
     */
    public function __construct(string $jobName)
    {
        $this->jobName = $jobName;
        $this->lastStatsDate = time();
    }

    /**
     * @param RepositoryInterface $repository
     * @param IterableResult $iterableResult
     */
    public function transferGeneric(RepositoryInterface $repository, IterableResult $iterableResult): void
    {
        $index = $this->index->getName();
        $this->logger->info(sprintf('Transferring Data from %s', $this->jobName), LogEnvironment::fromMethodName(__METHOD__));

        foreach ($iterableResult as $iteration) {
            $record = current($iteration);
            $this->autoBulkIndex($this->processor->convertRecordToDocument($record, $index));
            $this->logStats();
        }

        $this->flushBulkIndex();
        $this->logStats(true);
    }

    /**
     * @param bool $summary
     */
    protected function logStats(bool $summary = false): void
    {
        try {
            $this->counter++;
            if ($this->counter % $this->logEvery === 0 || $summary) {
                $secondsUsed = time() - $this->lastStatsDate;
                $rate = $secondsUsed > 0 ? number_format($this->logEvery / $secondsUsed, 2, '.', '') : '~';
                $this->logger->info(sprintf('Imported %s %s records in %s seconds (%s per second), Total: %s, Memory Usage: %s Mb', $this->logEvery, $this->jobName, $secondsUsed, $rate, $this->counter, memory_get_usage(true) / 1024 / 1024), LogEnvironment::fromMethodName(__METHOD__));
                $this->lastStatsDate = time();
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), LogEnvironment::fromMethodName(__METHOD__));
        }
    }

    /**
     * @param array $document
     */
    protected function autoBulkIndex(array $document): void
    {
        $this->bulkIndexStorage['body'][] = [
            'index' => [
                '_index' => $document['index'],
                '_id' => $document['id'] ?? null
            ]
        ];

        $this->bulkIndexStorage['body'][] = $document['body'];

        if ($this->bulkIndexStorageCounter % self::BULK_INDEX_SIZE === 0) {
            $this->flushBulkIndex();
        }

        $this->bulkIndexStorageCounter++;
    }

    public function flushBulkIndex(): void
    {
        if (count($this->bulkIndexStorage) === 0) {
            return;
        }

        $result = $this->index->bulk($this->bulkIndexStorage);

        foreach ($result['items'] as $resultDocument) {
            if ($resultDocument['index']['_shards']['failed'] !== 0) {
                $this->logger->error(sprintf('Ingesting a document in Elasticsearch failed. Details: %s', json_encode($resultDocument, JSON_THROW_ON_ERROR)));
            }
        }

        $this->bulkIndexStorage = [];
    }
}
