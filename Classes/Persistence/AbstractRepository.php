<?php
declare(strict_types=1);

namespace PunktDe\Analytics\Persistence;

/*
 *  (c) 2019 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use Doctrine\ORM\EntityManager;
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
     * @return string
     */
    abstract protected function getDataSourceName(): string;
}
