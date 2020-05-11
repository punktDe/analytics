<?php
declare(strict_types=1);

namespace PunktDe\Analytics\Persistence;

/*
 *  (c) 2019 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\Exception\UnknownObjectException;

abstract class AbstractRepository
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
     * @throws UnknownObjectException
     * @throws \Neos\Flow\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \Neos\Flow\ObjectManagement\Exception\CannotBuildObjectException
     */
    public function initializeObject(): void
    {
        $this->dataSource = $this->dataSourceFactory->getInstance($this->getDataSourceName());
    }

    /**
     * @return string
     */
    abstract protected function getDataSourceName(): string;
}
