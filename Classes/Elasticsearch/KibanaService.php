<?php
declare(strict_types=1);

namespace PunktDe\Analytics\Elasticsearch;

/*
 *  (c) 2019 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Neos\Flow\Annotations as Flow;

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
     * @var string
     * @Flow\InjectConfiguration(path="kibana.server.scheme")
     */
    protected $clientScheme;

    /**
     * @var string
     * @Flow\InjectConfiguration(path="kibana.server.host")
     */
    protected $clientHost;

    /**
     * @var int
     * @Flow\InjectConfiguration(path="kibana.server.port")
     */
    protected $clientPort;

    /**
     * @var string
     * @Flow\InjectConfiguration(path="kibana.server.user")
     */
    protected $clientUser;

    /**
     * @var string
     * @Flow\InjectConfiguration(path="kibana.server.pass")
     */
    protected $clientPassword;

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        if ($this->client === null) {
            $hostString = sprintf('%s://%s:%s', $this->clientScheme, $this->clientHost, $this->clientPort);

            $builder = ClientBuilder::create()
                ->setHosts([$hostString]);

            if (isset($this->clientUser, $this->clientPassword)) {
                $builder->setBasicAuthentication($this->clientUser, $this->clientPassword);
            }

            $this->client = $builder->build();
        }

        return $this->client;
    }
}
