<?php
declare(strict_types=1);

namespace PunktDe\Analytics\Elasticsearch;

/*
 *  (c) 2019 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

/**
 * @method delete(array $array)
 * @method index(array $params)
 * @method search(array $params)
 * @method get(array $params)
 * @method reindex(array $params)
 * @method bulk(array $params)
 */
interface IndexInterface
{
    /**
     * @return string
     */
    public function getName(): string;
}
