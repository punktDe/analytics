<?php
declare(strict_types=1);

namespace PunktDe\Analytics\Elasticsearch;

/*
 *  (c) 2019 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use Neos\Flow\Annotations as Flow;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

/**
 * @Flow\Scope("singleton")
 */
class KibanaService
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var array
     * @Flow\InjectConfiguration(path="kibana.server")
     */
    protected $clientConfiguration;

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        if ($this->client === null) {
            $this->client = ClientBuilder::create()
                ->setHosts([$this->clientConfiguration])
                ->build();
        }

        return $this->client;
    }
}
