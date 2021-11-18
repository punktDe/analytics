<?php
declare(strict_types=1);

namespace PunktDe\Analytics\Transfer;

/*
 *  (c) 2019 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;
use Psr\Log\LoggerInterface;
use PunktDe\Analytics\Elasticsearch\IndexInterface;
use PunktDe\Analytics\Processor\ElasticsearchProcessorInterface;
use Iterator;
use JsonException;

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
    protected $counter;

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
     * @param Iterator $iterator
     * @param bool $unWarpDoctrineArray
     * @throws JsonException
     */
    public function transferGeneric(Iterator $iterator, bool $unWarpDoctrineArray = false): void
    {
        $this->logger->info(sprintf('Transferring Data from %s', $this->jobName), LogEnvironment::fromMethodName(__METHOD__));

        foreach ($iterator as $record) {
            if ($unWarpDoctrineArray) {
                // Actually intended, see https://github.com/doctrine/orm/issues/5287
                $record = current($record);
            }

            try {
                $this->autoBulkIndex($this->processor->convertRecordToDocument($record, $this->index->getName()));
            } catch (\Exception $exception) {
                $this->logger->error(sprintf('Error while converting / transferring a record to elastic. Error %s on record %s', $exception->getMessage(), json_encode($record)), LogEnvironment::fromMethodName(__METHOD__));
                throw $exception;
            }

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
                $importedWithThisBatch = $this->counter % $this->logEvery === 0 ? $this->logEvery : $this->counter % $this->logEvery;
                $this->logger->info(sprintf('Imported %s %s records in %s seconds (%s per second), Total: %s, Memory Usage: %s Mb', $importedWithThisBatch, $this->jobName, $secondsUsed, $rate, $this->counter, memory_get_usage(true) / 1024 / 1024), LogEnvironment::fromMethodName(__METHOD__));
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

        if (count($this->bulkIndexStorage['body']) / 2 !== count($result['items'])) {
            $this->logger->error(sprintf('Bulk Request sent %s items but received %s acks', count($this->bulkIndexStorage['body']) / 2, count($result['items'])), LogEnvironment::fromMethodName(__METHOD__));
        }

        foreach ($result['items'] as $resultDocument) {
            if ($resultDocument['index']['_shards']['failed'] ?? 1 !== 0) {
                $this->logger->error(sprintf('Ingesting a document in Elasticsearch failed. Details: %s', json_encode($resultDocument, JSON_THROW_ON_ERROR)));
            }
        }

        $this->bulkIndexStorage = [];
    }
}
