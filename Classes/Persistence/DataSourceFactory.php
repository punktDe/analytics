<?php
declare(strict_types=1);

namespace PunktDe\Analytics\Persistence;

/*
 *  (c) 2019 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\Exception\InvalidConfigurationTypeException;
use Neos\Flow\ObjectManagement\Exception\CannotBuildObjectException;
use Neos\Flow\ObjectManagement\Exception\UnknownObjectException;
use Neos\Flow\ObjectManagement\ObjectManager;

class DataSourceFactory
{
    /**
     * @Flow\Inject
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @param string $databaseName
     * @return DataSource
     * @throws UnknownObjectException
     * @throws InvalidConfigurationTypeException
     * @throws CannotBuildObjectException
     */
    public function getInstance(string $databaseName): DataSource
    {
        return $this->objectManager->get(DataSource::class, $databaseName);
    }
}
