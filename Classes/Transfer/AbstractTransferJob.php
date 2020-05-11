<?php
declare(strict_types=1);

namespace PunktDe\Analytics\Transfer;

/*
 *  (c) 2019 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use Neos\Flow\Annotations as Flow;
use DateTime;
use Neos\Flow\Log\Utility\LogEnvironment;
use Psr\Log\LoggerInterface;
use PunktDe\Analytics\Elasticsearch\IndexInterface;

class AbstractTransferJob
{
    protected const BULK_INDEX_SIZE = 1000;

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
     * @param bool $summary
     */
    protected function logStats(bool $summary = false): void
    {
        try {
            $this->counter++;
            if ($this->counter % $this->logEvery === 0 || $summary) {
                $secondsUsed = time() - $this->lastStatsDate;
                $rate = $secondsUsed > 0 ? $this->counter / $secondsUsed : '~';
                $this->logger->info(sprintf('Imported %s %s records in %s seconds (%s per second)', $this->counter, $this->jobName, $secondsUsed, $rate), LogEnvironment::fromMethodName(__METHOD__));
                $this->counter = 0;
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

        $this->index->bulk($this->bulkIndexStorage);
        $this->bulkIndexStorage = [];
    }
}
