<?php
declare(strict_types=1);

namespace PunktDe\Analytics\Elasticsearch\IndexConfiguration;

/*
 *  (c) 2021 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

interface IndexConfigurationPostProcessorInterface
{
    public static function isSuitableFor(string $indexName): bool;

    public function process(array $indexConfiguration): array;
}
