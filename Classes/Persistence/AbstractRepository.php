<?php
declare(strict_types=1);

namespace PunktDe\Analytics\Persistence;

/*
 *  (c) 2019 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\Exception\UnknownObjectException;
use Psr\Log\LoggerInterface;

abstract class AbstractRepository implements RepositoryInterface
{
    /**
     * @Flow\Inject
     * @var DataSourceFactory
     */
    protected $dataSourceFactory;

    /**
     * @var DataSource
     */
    protected $dataSource;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @throws UnknownObjectException
     * @throws \Neos\Flow\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \Neos\Flow\ObjectManagement\Exception\CannotBuildObjectException
     */
    public function initializeObject(): void
    {
        $this->dataSource = $this->dataSourceFactory->getInstance($this->getDataSourceName());
    }

    public function getEntityManager(): EntityManager
    {
        return $this->dataSource->getEntityManager();
    }

    /**
     * @param string $query
     * @return ResultSetMapping
     * @throws EmptyResultException
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    protected function buildRsmByQuery(string $query): ResultSetMapping
    {
        $query .= PHP_EOL . 'LIMIT 1';

        $statement = $this->dataSource->getConnection()->prepare($query);
        $result = $statement->executeQuery();
        $firstRow = $result->fetchAssociative();

        if ($firstRow === false) {
            throw new EmptyResultException('No Data was returned', 1637241365);
        }

        $rsm = new ResultSetMappingBuilder($this->dataSource->getEntityManager());
        foreach (array_keys($firstRow) as $field) {
            $rsm->addScalarResult($field, $field);
        }

        return $rsm;
    }

    /**
     * @return string
     */
    abstract protected function getDataSourceName(): string;
}
