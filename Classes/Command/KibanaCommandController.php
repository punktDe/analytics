<?php
declare(strict_types=1);

namespace PunktDe\Analytics\Command;

/*
 *  (c) 2019 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Utility\Algorithms;
use PunktDe\Analytics\Elasticsearch\ElasticsearchService;
use PunktDe\Analytics\Elasticsearch\KibanaService;
use function foo\func;

class KibanaCommandController extends CommandController
{

    /**
     * @Flow\Inject
     * @var ElasticsearchService
     */
    protected $elasticsearchService;

    /**
     * @Flow\Inject
     * @var KibanaService
     */
    protected $kibanaService;

    /**
     * @param string $userName
     * @param string $email
     * @param string $fullName
     * @param string $password
     * @param string $space Defaults to mitarbeiter
     * @throws NoNodesAvailableException
     */
    public function createUserCommand(string $userName, string $email, string $fullName, string $password = '', string $space = 'mitarbeiter'): void
    {
        $showPassword = $password === '';

        $this->outputLine('Adding Kibana permissions');
        $this->addKibanaPermissions($userName, $space);

        if (trim($password) === '') {
            $password = Algorithms::generateRandomString(30);
        }

        $this->addUser($userName, $email, $fullName, $password);

        $this->outputLine();

        $this->outputLine('## Punkt.de Businessanalytics Account');
        $this->outputLine('| Key     |     Value                                   |');
        $this->outputLine('|---------|---------------------------------------------|');
        $this->outputLine('| URL     | https://kibana.businessanalytics.punkt.app/ |');
        $this->outputLine('| Documentation     | https://jira.pluspunkthosting.de/wiki/display/PUN/Business+Analytics+Dokumentation |');
        $this->outputLine(sprintf('|Username | %s |', $userName));
        $this->outputLine(sprintf('|Password | %s |', $showPassword ? $password : str_repeat('*', strlen($password))));
        $this->outputLine(sprintf('|eMail | %s |', $email));
    }

    /**
     * @param string $userName
     * @param string $space
     * @throws NoNodesAvailableException
     */
    private function addKibanaPermissions(string $userName, string $space): void
    {
        $url = '/api/security/role/user_' . $userName;

        $body = [
            'metadata' => [
                'version' => 1
            ],
            'elasticsearch' => [
                'cluster' => [],
                'indices' => [[
                    'names' => ['*.' . $userName],
                    'privileges' => ['read']
                ]]
            ],
            'kibana' => [
                [
                    'base' => [],
                    'feature' => [
                        'dashboard' => [
                            'read'
                        ]
                    ],
                    'spaces' => [$space]
                ]
            ]
        ];

        $options['client']['headers']['kbn-xsrf'] = ['kbn-xsrf' => 'reporting'];

        $return = $this->kibanaService->getClient()
            ->transport->performRequest(
                'PUT', $url, [], json_encode($body),
                $options
            );
    }

    /**
     * @param string $userName
     * @param string $email
     * @param string $fullName
     * @param string $password
     * @throws NoNodesAvailableException
     */
    private function addUser(string $userName, string $email, string $fullName, string $password): void
    {
        $url = '/_security/user/' . $userName;

        $body = [
            'password' => $password,
            'roles' => ['user_' . $userName],
            'full_name' => $fullName,
            'email' => $email,
        ];

        $this->elasticsearchService->getClient()->transport->performRequest(
            'POST', $url, [], json_encode($body)
        )->promise()->then(static function ($response) {
            $this->outputLine('User was added');
        }, static function ($response) {
            \Neos\Flow\var_dump($response, __METHOD__ . ':' . __LINE__);
        });
    }
}
