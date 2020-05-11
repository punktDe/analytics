<?php
declare(strict_types=1);

namespace PunktDe\Analytics\Persistence;

/*
 *  (c) 2019 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use Neos\Flow\Annotations as Flow;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Neos\Flow\Configuration\Exception\InvalidConfigurationException;
use Neos\Flow\Persistence\Doctrine\EntityManagerFactory;

final class DataSource
{
    /**
     * @Flow\Inject
     * @var EntityManagerFactory
     */
    protected $entityManagerFactory;

    /**
     * @Flow\InjectConfiguration(path="persistence")
     * @var array
     */
    protected $persistenceConfiguration;

    /**
     * @var string
     */
    private $selectedDatabase = '';

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * DataSource constructor.
     * @param string $database
     */
    public function __construct(string $database)
    {
        $this->selectedDatabase = $database;
    }

    public function initializeObject()
    {
        try {
            $this->connect();
        } catch (InvalidConfigurationException $e) {
            die('Connection failed: ' . $e->getMessage());
        }
    }

    /**
     * @return $this
     * @throws InvalidConfigurationException
     */
    public function connect(): self
    {
        if (!isset($this->persistenceConfiguration[$this->selectedDatabase])) {
            throw new \Exception(sprintf('No configuration found for %s, available are %s', $this->selectedDatabase, implode(',', array_keys($this->persistenceConfiguration))), 1568883099);
        }

        $this->entityManagerFactory->injectSettings(['persistence' => $this->persistenceConfiguration[$this->selectedDatabase]]);
        $this->entityManager = $this->entityManagerFactory->create();
        $this->connection = $this->entityManager->getConnection();

        return $this;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }
}
