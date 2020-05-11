<?php
declare(strict_types=1);

namespace PunktDe\Analytics\Command;

/*
 *  (c) 2019 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use DateInterval;
use DateTime;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Psr\Log\LoggerInterface;
use PunktDe\Analytics\Elasticsearch\ElasticsearchService;
use PunktDe\Analytics\Elasticsearch\IndexInterface;

abstract class AbstractTransferCommandController extends CommandController
{
    protected $indexName = '';

    /**
     * @Flow\Inject
     * @var ElasticsearchService
     */
    protected $elasticSearchService;

    /**
     * @var IndexInterface
     */
    protected $index;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    public function resetIndexCommand(): void
    {
        $this->outputLine('Recreating existing index ' . $this->indexName);
        $this->elasticSearchService->recreateElasticIndex($this->indexName);
    }

    /**
     * @return \DateTime
     * @throws \Exception
     */
    protected function getEndOfDayDate(): \DateTime
    {
        return (new \DateTime())->setTime(23, 59, 59);
    }

    /**
     * @param string $interval
     * @return DateTime
     * @throws \Exception
     */
    protected function getStartDate(string $interval): \DateTime
    {
        if ($interval === 'full') {
            $startDate = (new DateTime())->sub(new DateInterval('P30Y'));
        } else {
            $startDate = (new DateTime())->sub(new DateInterval($interval));
        }

        return $startDate->setTime(0, 0, 0);
    }
}
